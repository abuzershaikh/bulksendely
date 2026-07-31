const Extend = require('./extend');

/**
 * FlowEngine - Visual Chatbot Flow Executor
 */

class FlowEngine {
    
    static async handleMessage(WAZIPER, Common, instance_id, chat_id, content, message, subscriptor) {
        if (!content) return false;
        const msgText = content.toLowerCase().trim();

        try {
            // 1. Check if user is already in an active flow session
            let session = await Common.db_get("sp_chatbot_sessions", [{ phone_number: chat_id }, { instance_id: instance_id }]);
            
            if (session) {
                // User is in a flow, let's process the next step
                const flow = await Common.db_get("sp_chatbot_flows", [{ id: session.current_flow_id }]);
                if (flow && flow.status == 1) {
                    const canvas = JSON.parse(flow.canvas_data);
                    const currentNodeId = session.current_node_id;
                    
                    // Find the current node to understand what buttons were shown
                    const currentNode = canvas.nodes.find(n => n.uuid === currentNodeId);
                    
                    let sessionVariables = {};
                    try {
                        if (session.variables) {
                            sessionVariables = JSON.parse(session.variables);
                        }
                    } catch (e) {}

                    // SAVE VARIABLE LOGIC
                    if (currentNode && currentNode.type === 'template' && currentNode.propertiesJson && currentNode.propertiesJson.saveVariable) {
                        const varName = currentNode.propertiesJson.saveVariable.replace(/[{}]/g, '').trim();
                        if (varName) {
                            sessionVariables[varName] = content.trim(); // Save original content
                            await Common.db_update("sp_chatbot_sessions", [{ variables: JSON.stringify(sessionVariables) }, { id: session.id }]);
                        }
                    }

                    // Find out where to go next based on user input (button click)
                    const nextNodeId = this.getNextNode(canvas, currentNodeId, msgText, currentNode);
                    
                    if (nextNodeId) {
                        // Execute the next node, which might traverse conditions/gotos and return the final messaging node
                        const finalState = await this.executeNode(WAZIPER, Common, canvas, nextNodeId, instance_id, chat_id, message, sessionVariables, session.id, flow.id);
                        
                        if (finalState && finalState.nodeId) {
                            if (finalState.nodeId === 'ended') {
                                await Common.db_delete("sp_chatbot_sessions", [{ id: session.id }]);
                            } else {
                                await Common.db_update("sp_chatbot_sessions", [
                                    { current_flow_id: finalState.flowId, current_node_id: finalState.nodeId }, 
                                    { id: session.id }
                                ]);
                            }
                        }
                        return true; // Handled
                    } else {
                        // User sent something that doesn't match any button
                        console.log(`[FlowEngine] Invalid input '${msgText}' from ${chat_id}`);
                        
                        let unknownAction = 0; // Default: Resend menu
                        let unknownReply = '';
                        try {
                            const settings = await Common.db_get("sp_whatsapp_ai", [{ instance_id: instance_id }, { team_id: flow.team_id }]);
                            if (settings) {
                                unknownAction = parseInt(settings.unknown_message_action) || 0;
                                unknownReply = settings.unknown_message_reply || '';
                            }
                        } catch (e) {}
                        
                        if (unknownAction === 2) {
                            console.log(`[FlowEngine] Settings say: End session`);
                            await Common.db_delete("sp_chatbot_sessions", [{ id: session.id }]);
                            return true;
                        } else if (unknownAction === 1) {
                            console.log(`[FlowEngine] Settings say: Send custom reply`);
                            if (unknownReply && WAZIPER.sessions && WAZIPER.sessions[instance_id]) {
                                await WAZIPER.sessions[instance_id].sendMessage(chat_id, { text: unknownReply });
                            }
                            return true; 
                        } else {
                            console.log(`[FlowEngine] Settings say: Resend current node`);
                            await this.executeNode(WAZIPER, Common, canvas, currentNodeId, instance_id, chat_id, message, sessionVariables, session.id, flow.id);
                            return true; 
                        }
                    }
                } else {
                    // Flow doesn't exist or is disabled, delete session
                    await Common.db_delete("sp_chatbot_sessions", [{ id: session.id }]);
                }
            }

            // 2. If not in a session, check if message triggers any flow
            let allFlows = await Common.db_fetch("sp_chatbot_flows", [{ instance_id: instance_id }, { status: 1 }]);
            if (!allFlows || allFlows.length === 0) {
                // Fallback: search by team_id if instance_id was changed or re-scanned
                const sessionRecord = await Common.db_get("sp_whatsapp_sessions", [{ instance_id: instance_id }]);
                if (sessionRecord && sessionRecord.team_id) {
                    allFlows = await Common.db_fetch("sp_chatbot_flows", [{ team_id: sessionRecord.team_id }, { status: 1 }]);
                }
            }

            if (allFlows && allFlows.length > 0) {
                allFlows.sort((a, b) => b.id - a.id);
                for (let flow of allFlows) {
                    let canvas;
                    try {
                        canvas = JSON.parse(flow.canvas_data);
                    } catch(e) { continue; }
                    
                    if (!canvas.nodes || canvas.nodes.length === 0) continue;
                    
                    const firstNode = canvas.nodes[0];
                    const keywordsStr = (firstNode.propertiesJson?.keywords || firstNode.propertiesJson?.caption || '').toLowerCase();
                    const keywords = keywordsStr.split(',').map(k => k.trim()).filter(k => k.length > 0);
                    
                    if (keywords.length === 0) {
                        const caption = (firstNode.propertiesJson?.caption || '').toLowerCase().trim();
                        if (caption) keywords.push(caption);
                    }
                    
                    const matched = keywords.some(k => k && (msgText === k || msgText.split(/\s+/).includes(k)));
                    if (matched) {
                        console.log(`[FlowEngine] MATCH! Flow "${flow.name}" (ID: ${flow.id}) triggered by keyword "${msgText}" on instance ${instance_id}`);
                        
                        let sessionVariables = {};
                        // Create session first so we have an ID to pass
                        const insertId = await Common.db_insert("sp_chatbot_sessions", {
                            team_id: flow.team_id,
                            instance_id: instance_id,
                            phone_number: chat_id,
                            current_flow_id: flow.id,
                            current_node_id: firstNode.uuid,
                            variables: JSON.stringify(sessionVariables)
                        });
                        
                        const finalState = await this.executeNode(WAZIPER, Common, canvas, firstNode.uuid, instance_id, chat_id, message, sessionVariables, insertId, flow.id);
                        
                        if (finalState && finalState.nodeId) {
                            if (finalState.nodeId === 'ended') {
                                await Common.db_delete("sp_chatbot_sessions", [{ id: insertId }]);
                            } else {
                                await Common.db_update("sp_chatbot_sessions", [
                                    { current_flow_id: finalState.flowId, current_node_id: finalState.nodeId }, 
                                    { id: insertId }
                                ]);
                            }
                        }
                        
                        return true;
                    }
                }
            }
            
            return false;
        } catch (e) {
            console.error("[FlowEngine] Error:", e);
            return false;
        }
    }

    static getNextNode(canvas, currentNodeId, userInput, currentNode) {
        const outConns = (canvas.connections || []).filter(c => c.sourceNodeId === currentNodeId);
        if (outConns.length === 0) return null;
        
        if (outConns.length === 1 && (!currentNode?.buttons || currentNode.buttons.length === 0)) {
            return outConns[0].destNodeId;
        }
        
        if (currentNode && currentNode.buttons && currentNode.buttons.length > 0 && userInput) {
            for (let i = 0; i < currentNode.buttons.length; i++) {
                const buttonText = currentNode.buttons[i].toLowerCase().trim();
                if (userInput === buttonText) {
                    const matchConn = outConns.find(c => c.sourcePort === `btn_${i}`);
                    if (matchConn) {
                        return matchConn.destNodeId;
                    }
                }
            }
            return null;
        }
        
        return outConns[0].destNodeId;
    }

    static async executeNode(WAZIPER, Common, canvas, nodeId, instance_id, chat_id, message, sessionVariables, sessionId, flowId) {
        const node = canvas.nodes.find(n => n.uuid === nodeId);
        if (!node) return null;

        console.log(`[FlowEngine] Executing node: type=${node.type}, uuid=${nodeId}`);

        if (node.type === 'condition') {
            const props = node.propertiesJson || {};
            const varName = (props.conditionVar || '').replace(/[{}]/g, '').trim();
            const op = props.conditionOp || '==';
            const val = props.conditionVal || '';
            
            const actualVal = sessionVariables[varName] || '';
            let isTrue = false;
            
            if (op === '==') isTrue = (actualVal.toLowerCase() == val.toLowerCase());
            else if (op === '!=') isTrue = (actualVal.toLowerCase() != val.toLowerCase());
            else if (op === '>') isTrue = (parseFloat(actualVal) > parseFloat(val));
            else if (op === '<') isTrue = (parseFloat(actualVal) < parseFloat(val));
            else if (op === 'contains') isTrue = (actualVal.toLowerCase().includes(val.toLowerCase()));
            
            console.log(`[FlowEngine] Condition: ${varName}(${actualVal}) ${op} ${val} => ${isTrue}`);
            
            const nextPort = isTrue ? 'btn_0' : 'btn_1';
            const matchConn = (canvas.connections || []).find(c => c.sourceNodeId === nodeId && c.sourcePort === nextPort);
            
            if (matchConn) {
                return await this.executeNode(WAZIPER, Common, canvas, matchConn.destNodeId, instance_id, chat_id, message, sessionVariables, sessionId, flowId);
            } else {
                return null;
            }
        } 
        else if (node.type === 'go_to') {
            const props = node.propertiesJson || {};
            const targetFlowId = props.goToFlowId;
            if (targetFlowId) {
                console.log(`[FlowEngine] Jumping to flow ${targetFlowId}`);
                const newFlow = await Common.db_get("sp_chatbot_flows", [{ id: targetFlowId }]);
                if (newFlow && newFlow.status == 1) {
                    const newCanvas = JSON.parse(newFlow.canvas_data);
                    const firstNode = newCanvas.nodes[0];
                    if (firstNode) {
                        return await this.executeNode(WAZIPER, Common, newCanvas, firstNode.uuid, instance_id, chat_id, message, sessionVariables, sessionId, targetFlowId);
                    }
                }
            }
            return null;
        }

        let payload = null;

        if (node.type === 'template') {
            const props = node.propertiesJson || {};
            let title = props.title || '';
            let caption = props.caption || '';
            let footer = props.footer || '';
            
            // Replace variables in text
            for (let key in sessionVariables) {
                const regex = new RegExp(`{{${key}}}`, 'g');
                title = title.replace(regex, sessionVariables[key]);
                caption = caption.replace(regex, sessionVariables[key]);
                footer = footer.replace(regex, sessionVariables[key]);
            }
            
            const buttons = node.buttons || [];
            const textMsg = title ? `*${title}*

${caption}` : caption;
            
            if (buttons.length > 0) {
                const templateButtons = buttons.map((b, i) => {
                    return {
                        index: i + 1,
                        quickReplyButton: {
                            displayText: b,
                            id: `btn_${i}`
                        }
                    };
                });
                
                if (WAZIPER.sessions && WAZIPER.sessions[instance_id]) {
                    try {
                        await Extend.sendTemplateButtons(WAZIPER.sessions[instance_id], chat_id, textMsg, templateButtons, footer);
                        console.log(`[FlowEngine] Interactive template buttons sent to ${chat_id}`);
                    } catch (err) {
                        console.error(`[FlowEngine] Failed to send interactive buttons to ${chat_id}:`, err.message);
                        await WAZIPER.sessions[instance_id].sendMessage(chat_id, {
                            text: `${textMsg}${footer ? '\n\n_' + footer + '_' : ''}\n\n${buttons.map((b, i) => `${i+1}. ${b}`).join('\n')}`
                        });
                    }
                }
                return { flowId, nodeId };
            } else {
                payload = { text: textMsg };
            }
        } else if (node.type === 'end') {
            const props = node.propertiesJson || {};
            let endMsg = props.endMessage || 'Thank you! Conversation ended. ';
            
            // Replace variables in text
            for (let key in sessionVariables) {
                const regex = new RegExp(`{{${key}}}`, 'g');
                endMsg = endMsg.replace(regex, sessionVariables[key]);
            }
            
            payload = { text: endMsg };
        }

        if (payload) {
            if (WAZIPER.sessions && WAZIPER.sessions[instance_id]) {
                try {
                    await WAZIPER.sessions[instance_id].sendMessage(chat_id, payload);
                    console.log(`[FlowEngine] Message sent to ${chat_id}`);
                } catch (err) {
                    console.error(`[FlowEngine] Failed to send message to ${chat_id}:`, err.message);
                }
            }
        }

        if (node.type === 'end') {
            return { flowId, nodeId: 'ended' };
        }
        
        return { flowId, nodeId };
    }
}

module.exports = FlowEngine;
