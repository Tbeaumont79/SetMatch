import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
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

    connect() {
        console.log("Chat controller connecté !");
        this.isOpen = false;
        this.currentChatId = null;
        this.loadChats();
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
    }

    async loadChats() {
        try {
            const response = await fetch("/chat");
            const html = await response.text();

            // Créer un document temporaire pour parser le HTML
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = html;

            // Extraire la liste des chats et l'injecter
            const chatListContent = tempDiv.querySelector(".chat-list-content");
            if (chatListContent) {
                this.chatListTarget.innerHTML = chatListContent.innerHTML;
            } else {
                // Si pas de chats, afficher le message par défaut
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
                    <button class="btn btn-primary btn-sm mt-2" data-action="click->chat#showNewChatModal">
                        Démarrer une conversation
                    </button>
                </div>
            </div>
        `;
    }

    async openChat(event) {
        const chatId = event.currentTarget.dataset.chatId;
        const chatTitle = event.currentTarget.dataset.chatTitle;

        this.currentChatId = chatId;
        this.conversationTitleTarget.textContent = chatTitle;

        // Afficher la vue de conversation
        this.chatListTarget.style.display = "none";
        this.conversationViewTarget.classList.remove("hidden");
        this.titleTarget.textContent = chatTitle;

        // Charger les messages
        await this.loadMessages();
    }

    async loadMessages() {
        if (!this.currentChatId) return;

        try {
            const response = await fetch(
                `/chat/${this.currentChatId}/messages`
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
            const messageDiv = document.createElement("div");
            messageDiv.className = `message ${
                message.is_mine ? "mine" : "theirs"
            }`;

            const bubbleClass = message.is_mine
                ? "chat-bubble-primary"
                : "chat-bubble-secondary";
            const alignment = message.is_mine ? "chat-end" : "chat-start";

            messageDiv.innerHTML = `
                <div class="chat ${alignment}">
                    <div class="chat-header">
                        ${message.author.email.split("@")[0]}
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
        });

        // Faire défiler vers le bas
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }

    async sendMessage(event) {
        event.preventDefault();

        if (!this.currentChatId) return;

        const content = this.messageInputTarget.value.trim();
        if (!content) return;

        try {
            const response = await fetch(`/chat/${this.currentChatId}/send`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ content }),
            });

            if (response.ok) {
                const message = await response.json();

                // Ajouter le message à l'affichage
                const messageDiv = document.createElement("div");
                messageDiv.className = "message mine";
                messageDiv.innerHTML = `
                    <div class="chat chat-end">
                        <div class="chat-header">
                            Vous
                            <time class="text-xs opacity-50">${this.formatTime(
                                message.created_at
                            )}</time>
                        </div>
                        <div class="chat-bubble chat-bubble-primary">
                            ${message.content}
                        </div>
                    </div>
                `;

                this.messagesTarget.appendChild(messageDiv);
                this.messagesTarget.scrollTop =
                    this.messagesTarget.scrollHeight;

                // Vider le champ de saisie
                this.messageInputTarget.value = "";
            }
        } catch (error) {
            console.error("Erreur lors de l'envoi du message:", error);
        }
    }

    backToList() {
        this.conversationViewTarget.classList.add("hidden");
        this.chatListTarget.style.display = "block";
        this.titleTarget.textContent = "Conversations";
        this.currentChatId = null;
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

    // Méthode pour fermer le modal en cliquant à l'extérieur
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
                `/users/search?q=${encodeURIComponent(query)}`
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

        // Vider d'abord les résultats
        this.searchResultsTarget.innerHTML = "";

        // Créer chaque élément utilisateur individuellement
        users.forEach((user) => {
            const userDiv = document.createElement("div");
            userDiv.className =
                "flex items-center gap-3 p-2 hover:bg-base-200 rounded cursor-pointer";
            userDiv.dataset.action = "click->chat#startChat";
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

    async startChat(event) {
        event.preventDefault();
        console.log("startChat appelé", event.currentTarget);

        const userId = event.currentTarget.dataset.userId;
        const userName = event.currentTarget.dataset.userName;

        console.log("User ID:", userId, "User Name:", userName);

        if (!userId) {
            console.error("userId manquant");
            return;
        }

        try {
            const response = await fetch("/chat/start", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({ participant_id: parseInt(userId) }),
            });

            console.log("Response status:", response.status);

            if (response.ok) {
                const data = await response.json();
                console.log("Response data:", data);

                // Fermer la modal
                this.closeNewChatModal();

                // Ouvrir le chat
                this.currentChatId = data.chat_id;
                this.conversationTitleTarget.textContent = userName;
                this.chatListTarget.style.display = "none";
                this.conversationViewTarget.classList.remove("hidden");
                this.titleTarget.textContent = userName;

                // Charger les messages
                await this.loadMessages();
            } else {
                const errorData = await response.json();
                console.error("Erreur serveur:", errorData);
            }
        } catch (error) {
            console.error("Erreur lors de la création du chat:", error);
        }
    }

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString("fr-FR", {
            hour: "2-digit",
            minute: "2-digit",
        });
    }
}
