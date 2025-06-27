import { Controller } from "@hotwired/stimulus";

/**
 * Contrôleur de base pour les fonctionnalités chat communes
 * Respecte les principes SRP et DRY
 */
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
        this.isOpen = false;
        this.currentChatId = null;
        this.initializeController();
    }

    /**
     * Méthode abstraite à implémenter par les classes filles
     * Respecte OCP - ouverte à l'extension
     */
    initializeController() {
        // À implémenter dans les classes héritées
    }

    // === Gestion de l'interface ===

    toggle() {
        this.isOpen ? this.close() : this.open();
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
        this.cleanup();
    }

    /**
     * Méthode virtuelle pour le nettoyage
     * Respecte LSP - peut être surchargée
     */
    cleanup() {
        // Override dans les classes filles si nécessaire
    }

    backToList() {
        this.chatListTarget.style.display = "block";
        this.conversationViewTarget.classList.add("hidden");
        this.titleTarget.textContent = "Messages";
        this.currentChatId = null;
    }

    // === Gestion des chats ===

    async loadChats() {
        try {
            const response = await this.fetchWithCredentials("/chat");
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
        this.chatListTarget.innerHTML = this.getEmptyChatListTemplate();
    }

    getEmptyChatListTemplate() {
        return `
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

    async openChat(event) {
        const chatId = event.currentTarget.dataset.chatId;
        const chatTitle = event.currentTarget.dataset.chatTitle;

        this.currentChatId = chatId;
        this.conversationTitleTarget.textContent = chatTitle;

        this.chatListTarget.style.display = "none";
        this.conversationViewTarget.classList.remove("hidden");
        this.titleTarget.textContent = chatTitle;

        await this.loadMessages();
        this.onChatOpened(chatId);
    }

    /**
     * Hook pour les classes filles
     * Respecte OCP - extensible sans modification
     */
    onChatOpened(chatId) {
        // Override dans les classes filles
    }

    // === Gestion des messages ===

    async loadMessages() {
        if (!this.currentChatId) return;

        try {
            const response = await this.fetchWithCredentials(
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
        messages.forEach((message) => this.addMessageToDisplay(message));
        this.scrollToBottom();
    }

    addMessageToDisplay(message) {
        const messageElement = this.createMessageElement(message);
        this.messagesTarget.appendChild(messageElement);
    }

    createMessageElement(message) {
        const messageDiv = document.createElement("div");
        messageDiv.className = `message ${message.is_mine ? "mine" : "theirs"}`;

        const bubbleClass = message.is_mine
            ? "chat-bubble-primary"
            : "chat-bubble-secondary";
        const alignment = message.is_mine ? "chat-end" : "chat-start";
        const authorName =
            message.author.display_name || message.author.email.split("@")[0];

        messageDiv.innerHTML = `
            <div class="chat ${alignment}">
                <div class="chat-header">
                    ${authorName}
                    <time class="text-xs opacity-50">${this.formatTime(
                        message.created_at
                    )}</time>
                </div>
                <div class="chat-bubble ${bubbleClass}">
                    ${message.content}
                </div>
            </div>
        `;

        return messageDiv;
    }

    scrollToBottom() {
        this.messagesTarget.scrollTop = this.messagesTarget.scrollHeight;
    }

    // === Gestion des modals ===

    showNewChatModal() {
        this.newChatModalTarget.classList.remove("hidden");
        this.userSearchTarget.focus();
    }

    closeNewChatModal() {
        this.newChatModalTarget.classList.add("hidden");
        this.userSearchTarget.value = "";
        this.searchResultsTarget.innerHTML = "";
    }

    closeModalOnBackdrop(event) {
        if (event.target === this.newChatModalTarget) {
            this.closeNewChatModal();
        }
    }

    // === Recherche d'utilisateurs ===

    async searchUsers(event) {
        const query = event.target.value.trim();

        if (query.length < 2) {
            this.searchResultsTarget.innerHTML = "";
            return;
        }

        try {
            const response = await this.fetchWithCredentials(
                `/api/users/search?q=${encodeURIComponent(query)}`
            );
            const users = await response.json();
            this.displaySearchResults(users);
        } catch (error) {
            console.error("Erreur lors de la recherche:", error);
        }
    }

    displaySearchResults(users) {
        if (users.length === 0) {
            this.searchResultsTarget.innerHTML =
                "<p class='p-4 text-gray-500'>Aucun utilisateur trouvé</p>";
            return;
        }

        this.searchResultsTarget.innerHTML = users
            .map(
                (user) => `
            <div class="p-3 hover:bg-gray-100 cursor-pointer border-b"
                 data-action="click->mercure-chat#startChat"
                 data-user-id="${user.id}"
                 data-user-name="${user.display_name}">
                <div class="flex items-center">
                    <div class="avatar placeholder mr-3">
                        <div class="bg-neutral-focus text-neutral-content rounded-full w-8">
                            <span class="text-xs">${user.display_name
                                .charAt(0)
                                .toUpperCase()}</span>
                        </div>
                    </div>
                    <div>
                        <div class="font-medium">${user.display_name}</div>
                        <div class="text-sm text-gray-500">${user.email}</div>
                    </div>
                </div>
            </div>
        `
            )
            .join("");
    }

    // === Utilitaires ===

    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString("fr-FR", {
            hour: "2-digit",
            minute: "2-digit",
        });
    }

    async fetchWithCredentials(url, options = {}) {
        return fetch(url, {
            credentials: "same-origin",
            ...options,
        });
    }

    // === Méthodes abstraites ===

    async sendMessage(event) {
        throw new Error("sendMessage method must be implemented by subclass");
    }

    async startChat(event) {
        throw new Error("startChat method must be implemented by subclass");
    }
}
