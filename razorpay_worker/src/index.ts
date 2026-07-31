interface Env {
	RAZORPAY_KEY_ID: string;
	RAZORPAY_KEY_SECRET: string;
}

interface OrderRequest {
	amount: number;
	currency?: string;
	receipt?: string;
	plan_id?: string;
	email?: string;
}

interface VerifyRequest {
	razorpay_order_id: string;
	razorpay_payment_id: string;
	razorpay_signature: string;
}

const CORS_HEADERS: Record<string, string> = {
	"Access-Control-Allow-Origin": "*",
	"Access-Control-Allow-Methods": "GET, POST, OPTIONS",
	"Access-Control-Allow-Headers": "Content-Type, Authorization",
	"Access-Control-Max-Age": "86400",
};

function jsonResponse(data: unknown, status = 200): Response {
	return new Response(JSON.stringify(data), {
		status,
		headers: { "Content-Type": "application/json", ...CORS_HEADERS },
	});
}

function errorResponse(message: string, status = 500): Response {
	return jsonResponse({ status: "error", message }, status);
}

export default {
	async fetch(request: Request, env: Env): Promise<Response> {
		// Handle CORS preflight for all routes
		if (request.method === "OPTIONS") {
			return new Response(null, { status: 204, headers: CORS_HEADERS });
		}

		const url = new URL(request.url);
		const path = url.pathname;

		try {
			if (path === "/health" && request.method === "GET") {
				return jsonResponse({ status: "ok", timestamp: Date.now() });
			}

			if (path === "/create-order" && request.method === "POST") {
				return await handleCreateOrder(request, env);
			}

			if (path === "/verify-payment" && request.method === "POST") {
				return await handleVerifyPayment(request, env);
			}

			return errorResponse("Not Found", 404);
		} catch (err: unknown) {
			const message = err instanceof Error ? err.message : "Internal Server Error";
			console.error("Worker error:", message);
			return errorResponse(message, 500);
		}
	},
};

// ───────── Create Order ─────────
async function handleCreateOrder(request: Request, env: Env): Promise<Response> {
	const body = (await request.json()) as OrderRequest;

	if (!body.amount || body.amount <= 0) {
		return errorResponse("Invalid amount", 400);
	}

	const amountInPaise = Math.round(body.amount * 100);

	const razorpayResponse = await fetch("https://api.razorpay.com/v1/orders", {
		method: "POST",
		headers: {
			"Content-Type": "application/json",
			Authorization: "Basic " + btoa(`${env.RAZORPAY_KEY_ID}:${env.RAZORPAY_KEY_SECRET}`),
		},
		body: JSON.stringify({
			amount: amountInPaise,
			currency: body.currency || "INR",
			receipt: body.receipt || `rcpt_${Date.now()}`,
			notes: {
				plan_id: body.plan_id || "",
				email: body.email || "",
			},
		}),
	});

	if (!razorpayResponse.ok) {
		const errText = await razorpayResponse.text();
		console.error("Razorpay order error:", errText);
		return errorResponse(`Razorpay order creation failed: ${razorpayResponse.status}`, 502);
	}

	const order = await razorpayResponse.json();
	return jsonResponse({ status: "success", order });
}

// ───────── Verify Payment ─────────
async function handleVerifyPayment(request: Request, env: Env): Promise<Response> {
	const body = (await request.json()) as VerifyRequest;

	if (!body.razorpay_order_id || !body.razorpay_payment_id || !body.razorpay_signature) {
		return errorResponse("Missing required fields", 400);
	}

	const payload = `${body.razorpay_order_id}|${body.razorpay_payment_id}`;

	const encoder = new TextEncoder();
	const keyData = encoder.encode(env.RAZORPAY_KEY_SECRET);

	const cryptoKey = await crypto.subtle.importKey(
		"raw",
		keyData,
		{ name: "HMAC", hash: "SHA-256" },
		false,
		["sign"],
	);

	const signatureBuffer = await crypto.subtle.sign("HMAC", cryptoKey, encoder.encode(payload));

	const expectedSignature = Array.from(new Uint8Array(signatureBuffer))
		.map((byte) => byte.toString(16).padStart(2, "0"))
		.join("");

	if (expectedSignature !== body.razorpay_signature) {
		return errorResponse("Payment verification failed — invalid signature", 400);
	}

	// Optionally: fetch payment details from Razorpay to double-check
	const paymentCheck = await fetch(
		`https://api.razorpay.com/v1/payments/${body.razorpay_payment_id}`,
		{
			headers: {
				Authorization: "Basic " + btoa(`${env.RAZORPAY_KEY_ID}:${env.RAZORPAY_KEY_SECRET}`),
			},
		},
	);

	if (!paymentCheck.ok) {
		return errorResponse("Could not verify payment with Razorpay", 502);
	}

	const paymentData = (await paymentCheck.json()) as Record<string, unknown>;

	if (paymentData.status !== "captured") {
		return errorResponse(`Payment not captured. Status: ${paymentData.status}`, 400);
	}

	return jsonResponse({
		status: "success",
		message: "Payment verified successfully",
		payment_id: body.razorpay_payment_id,
		order_id: body.razorpay_order_id,
	});
}
