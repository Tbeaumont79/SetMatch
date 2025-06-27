import { Controller } from "@hotwired/stimulus";
import BaseChatController from "./base_chat_controller.js";

/**
 * Contrôleur Chat avec Mercure (temps réel)
 * Hérite de BaseChatController - respecte LSP
 */
export default class extends BaseChatController {
    static targets = [
        "toggle",
        "window",
        "badge",
        "title",
        "chatList",
        "conversationView",
        "conversationTitle",
        "messages",
        "messageForm",
        "messageInput",
        "newChatModal",
        "userSearch",
        "searchResults",
    ];

    initializeController() {
        console.log("Mercure Chat controller connecté !");
        this.isOpen = false;
        this.currentChatId = null;
        this.eventSource = null;
        this.mercureJWT = null;
        this.mercureUrl = null;
        this.initializeMercure();
        this.loadChats();
    }

    cleanup() {
        super.cleanup();
        this.closeMercureConnection();
    }

    onChatOpened(chatId) {
        super.onChatOpened(chatId);
        this.connectToMercure(chatId);
    }

    // === Fonctionnalités Mercure ===

    async initializeMercure() {
        try {
            const response = await this.fetchWithCredentials("/api/chat/jwt");
            const data = await response.json();

            this.mercureJWT = data.jwt;
            this.mercureUrl = data.mercure_url;

            console.log(
                "JWT Mercure récupéré, prêt pour les connexions temps réel"
            );
        } catch (error) {
            console.error("Erreur lors de l'initialisation Mercure:", error);
        }
    }

    connectToMercure(chatId) {
        if (!this.mercureJWT || !this.mercureUrl) {
            console.warn("JWT ou URL Mercure manquants");
            return;
        }

        this.closeMercureConnection();

        const topic = `chat/${chatId}`;
        const url = new URL(this.mercureUrl);
        url.searchParams.append("topic", topic);

        this.eventSource = new EventSource(url.toString(), {
            headers: {
                Authorization: `Bearer ${this.mercureJWT}`,
            },
        });

        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                if (data.type === "new_message" && data.chat_id == chatId) {
                    // Éviter de dupliquer son propre message
                    if (!data.message.is_mine) {
                        this.addMessageToDisplay(data.message);
                        this.scrollToBottom();
                    }
                }
            } catch (error) {
                console.error(
                    "Erreur lors du parsing du message Mercure:",
                    error
                );
            }
        };

        this.eventSource.onerror = (error) => {
            console.error("Erreur EventSource:", error);
        };

        console.log(`Connecté à Mercure pour le chat ${chatId}`);
    }

    closeMercureConnection() {
        if (this.eventSource) {
            this.eventSource.close();
            this.eventSource = null;
            console.log("Connexion Mercure fermée");
        }
    }

    // === Implémentation des méthodes abstraites ===

    async sendMessage(event) {
        event.preventDefault();

        if (!this.currentChatId) return;

        const content = this.messageInputTarget.value.trim();
        if (!content) return;

        try {
            const response = await this.fetchWithCredentials(
                `/chat/${this.currentChatId}/send`,
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                    },
                    body: JSON.stringify({ content }),
                }
            );

            if (response.ok) {
                const message = await response.json();
                // Ajouter le message localement (Mercure gèrera les autres utilisateurs)
                this.addMessageToDisplay(message);
                this.scrollToBottom();
                this.messageInputTarget.value = "";
            } else {
                const errorData = await response.json();
                console.error("Erreur serveur:", errorData);
            }
        } catch (error) {
            console.error("Erreur lors de l'envoi du message:", error);
        }
    }

    async startChat(event) {
        event.preventDefault();

        const userId = event.currentTarget.dataset.userId;
        const userName = event.currentTarget.dataset.userName;

        if (!userId) {
            console.error("userId manquant");
            return;
        }

        let responseClone;

        try {
            const response = await this.fetchWithCredentials("/chat/start", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ participant_id: parseInt(userId) }),
            });

            responseClone = response.clone();

            if (response.ok) {
                const data = await response.json();
                this.closeNewChatModal();

                this.currentChatId = data.chat_id;
                this.conversationTitleTarget.textContent = userName;
                this.chatListTarget.style.display = "none";
                this.conversationViewTarget.classList.remove("hidden");
                this.titleTarget.textContent = userName;

                await this.loadMessages();
                this.connectToMercure(data.chat_id);
            } else {
                const errorText = await responseClone.text();
                console.error("Erreur serveur - Status:", response.status);
                console.error("Réponse reçue:", errorText);
            }
        } catch (error) {
            console.error("Erreur lors de la création du chat:", error);

            if (responseClone) {
                try {
                    const errorText = await responseClone.text();
                    console.error(
                        "Contenu de la réponse qui a causé l'erreur:",
                        errorText
                    );
                } catch (readError) {
                    console.error("Impossible de lire la réponse:", readError);
                }
            }
        }
    }

    toggle() {
        if (this.isOpen) {
            this.close();
        } else {
            this.open();
        }
    }

    open() {
        this.windowTarget.classList.remove("hidden");
        this.isOpen = true;
        this.loadChats();
    }

    close() {
        this.windowTarget.classList.add("hidden");
        this.isOpen = false;
        this.backToList();
        this.closeMercureConnection();
    }

    async loadChats() {
        try {
            const response = await fetch("/chat", {
                credentials: "same-origin",
            });
            const html = await response.text();

            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = html;

            const chatListContent = tempDiv.querySelector(".chat-list-content");
            if (chatListContent) {
                this.chatListTarget.innerHTML = chatListContent.innerHTML;
            } else {
                this.showEmptyChatList();
            }
        } catch (error) {
            console.error("Erreur lors du chargement des chats:", error);
            this.showEmptyChatList();
        }
    }

    showEmptyChatList() {
        this.chatListTarget.innerHTML = `
            <div class="p-4">
                <div class="text-center text-gray-500 py-8">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto mb-4 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-3.582 8-8 8a8.955 8.955 0 01-4.126-.98L3 20l1.98-5.874A8.955 8.955 0 013 12c0-4.418 3.582-8 8-8s8 3.582 8 8z" />
                    </svg>
                    <p>Aucune conversation</p>
                    <button class="btn btn-primary btn-sm mt-2" data-action="click->mercure-chat#showNewChatModal">
                        Démarrer une conversation
                    </button>
                </div>
            </div>
        `;
    }

    async loadMessages() {
        if (!this.currentChatId) return;

        try {
            const response = await fetch(
                `/chat/${this.currentChatId}/messages`,
                {
                    credentials: "same-origin",
                }
            );
            const messages = await response.json();

            this.displayMessages(messages);
        } catch (error) {
            console.error("Erreur lors du chargement des messages:", error);
        }
    }

    displayMessages(messages) {
        this.messagesTarget.innerHTML = "";

        messages.forEach((message) => {
            this.addMessageToDisplay(message);
        });

        this.scrollToBottom();
    }

    addMessageToDisplay(message) {
        const messageDiv = document.createElement("div");
        messageDiv.className = `message ${message.is_mine ? "mine" : "theirs"}`;

        const bubbleClass = message.is_mine
            ? "chat-bubble-primary"
            : "chat-bubble-secondary";
        const alignment = message.is_mine ? "chat-end" : "chat-start";

        messageDiv.innerHTML = `
            <div class="chat ${alignment}">
                <div class="chat-header">
                    ${
                        message.author.display_name ||
                        message.author.email.split("@")[0]
                    }
                    <time class="text-xs opacity-50">${this.formatTime(
                        message.created_at
                    )}</time>
                </div>
                <div class="chat-bubble ${bubbleClass}">
                    ${message.content}
                </div>
            </div>
        `;

        this.messagesTarget.appendChild(messageDiv);
    }

    scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }

    backToList() {
        this.conversationViewTarget.classList.add("hidden");
        this.chatListTarget.style.display = "block";
        this.titleTarget.textContent = "Conversations";
        this.currentChatId = null;
        this.closeMercureConnection();
    }

    showNewChatModal() {
        this.newChatModalTarget.classList.add("modal-open");
        this.userSearchTarget.value = "";
        this.searchResultsTarget.innerHTML = "";
    }

    closeNewChatModal() {
        this.newChatModalTarget.classList.remove("modal-open");
        this.userSearchTarget.value = "";
        this.searchResultsTarget.innerHTML = "";
    }

    closeModalOnBackdrop(event) {
        if (event.target === this.newChatModalTarget) {
            this.closeNewChatModal();
        }
    }

    async searchUsers(event) {
        const query = event.target.value.trim();

        if (query.length < 2) {
            this.searchResultsTarget.innerHTML = "";
            return;
        }

        try {
            const response = await fetch(
                `/api/users/search?q=${encodeURIComponent(query)}`,
                {
                    credentials: "same-origin",
                }
            );
            const users = await response.json();

            this.displaySearchResults(users);
        } catch (error) {
            console.error("Erreur lors de la recherche d'utilisateurs:", error);
        }
    }

    displaySearchResults(users) {
        if (users.length === 0) {
            this.searchResultsTarget.innerHTML =
                '<p class="text-gray-500 text-center py-4">Aucun utilisateur trouvé</p>';
            return;
        }

        this.searchResultsTarget.innerHTML = "";

        users.forEach((user) => {
            const userDiv = document.createElement("div");
            userDiv.className =
                "flex items-center gap-3 p-2 hover:bg-base-200 rounded cursor-pointer";
            userDiv.dataset.action = "click->mercure-chat#startChat";
            userDiv.dataset.userId = user.id;
            userDiv.dataset.userName = user.display_name;

            userDiv.innerHTML = `
                <div class="avatar">
                    <div class="w-8 rounded-full">
                        <img src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp" alt="Avatar" />
                    </div>
                </div>
                <div>
                    <div class="font-semibold">${user.display_name}</div>
                    <div class="text-sm text-gray-500">${user.email}</div>
                </div>
            `;

            this.searchResultsTarget.appendChild(userDiv);
        });
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString("fr-FR", {
            hour: "2-digit",
            minute: "2-digit",
        });
    }
}
