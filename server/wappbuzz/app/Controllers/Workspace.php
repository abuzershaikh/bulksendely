<?php

namespace App\Controllers;

class Workspace extends BaseController
{
    public function crm()
    {
        return $this->modulePage(
            "CRM",
            "Manage pipelines, deal stages, lead notes, and follow-ups from one workspace.",
            [
                ["title" => "Lead Inbox", "desc" => "Capture new WhatsApp, web, and campaign leads into one queue.", "badge" => "Live"],
                ["title" => "Pipeline Tracking", "desc" => "Move deals through stages with owners, notes, and reminders.", "badge" => "Sales"],
                ["title" => "Follow-up Board", "desc" => "Prioritize callbacks, demos, and nurture steps for the team.", "badge" => "Action"],
            ]
        );
    }

    public function ai_agent()
    {
        return $this->modulePage(
            "AI Agent",
            "Configure automated assistants for replies, qualification, routing, and campaign guidance.",
            [
                ["title" => "Conversation Brain", "desc" => "Tune prompts, tone, and business rules for your AI assistant.", "badge" => "Prompt"],
                ["title" => "Intent Routing", "desc" => "Send chats to human agents, campaigns, or workflows based on intent.", "badge" => "Route"],
                ["title" => "Knowledge Stack", "desc" => "Attach FAQs, product notes, and SOPs to power responses.", "badge" => "Docs"],
            ]
        );
    }

    public function form_builder()
    {
        return $this->modulePage(
            "Form Builder",
            "Create lead forms, intake flows, and campaign capture pages that feed your workspace.",
            [
                ["title" => "Drag-and-Drop Forms", "desc" => "Assemble forms for lead capture, onboarding, and support intake.", "badge" => "Build"],
                ["title" => "Field Logic", "desc" => "Use conditional sections to personalize the flow based on answers.", "badge" => "Logic"],
                ["title" => "Submission Sync", "desc" => "Push responses into CRM, campaigns, and WhatsApp follow-ups.", "badge" => "Sync"],
            ]
        );
    }

    protected function modulePage($title, $description, array $cards)
    {
        $content = view("workspace/module_page", [
            "title" => $title,
            "description" => $description,
            "cards" => $cards,
        ]);

        return view("Core\\Dashboard\\Views\\index", [
            "config" => [
                "id" => "workspace-".strtolower(str_replace(" ", "-", $title)),
                "name" => $title,
                "desc" => $description,
            ],
            "title" => $title,
            "content" => $content,
        ]);
    }
}
