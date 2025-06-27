import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["feed"];

    connect() {
        this.eventSource = null;
        // Vérifier si Mercure est disponible avant de se connecter
        this.checkMercureAndConnect();
    }

    disconnect() {
        if (this.eventSource) {
            this.eventSource.close();
        }
    }

    async checkMercureAndConnect() {
        try {
            const response = await fetch(
                "http://localhost:3000/.well-known/mercure?topic=test",
                {
                    method: "HEAD",
                }
            );

            // Si Mercure répond, on peut se connecter
            if (response.status !== 0) {
                console.log("Mercure disponible, connexion...");
                this.initializeMercure();
            }
        } catch (error) {
            console.log(
                "Mercure non disponible, mode hors ligne pour les posts"
            );
            // Ne pas se connecter à Mercure si non disponible
        }
    }

    initializeMercure() {
        const isViteServer = window.location.port === "5174";
        const topic = "posts";

        let url;

        if (isViteServer) {
            const hubUrl = "/mercure";
            url = new URL(hubUrl, window.location.origin);
            url.searchParams.append("topic", topic);
        } else {
            const localHubUrl = "http://localhost:3000/.well-known/mercure";
            url = new URL(localHubUrl);
            url.searchParams.append("topic", topic);
        }

        this.eventSource = new EventSource(url);

        this.eventSource.onmessage = (event) => {
            try {
                const data = JSON.parse(event.data);
                console.log("📨 Nouveau post reçu:", data);

                if (data.action === "created") {
                    this.addNewPost(data);
                } else if (data.action === "updated") {
                    this.updateExistingPost(data);
                }
            } catch (error) {
                console.error("❌ Erreur parsing Mercure data:", error);
            }
        };

        this.eventSource.onerror = (error) => {
            console.warn(
                "Connexion Mercure interrompue pour les posts, passage en mode hors ligne"
            );
            // Fermer proprement la connexion en cas d'erreur
            if (this.eventSource) {
                this.eventSource.close();
                this.eventSource = null;
            }
        };
    }

    addNewPost(postData) {
        const postHtml = `
            <div class="card bg-base-100 shadow-lg animate-bounce-in">
                <div class="card-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar">
                            <div class="w-10 rounded-full">
                                <img src="https://img.daisyui.com/images/stock/photo-1534528741775-53994a69daeb.webp" alt="Avatar par défaut" />
                            </div>
                        </div>
                        <div>
                            <div class="font-semibold">${
                                postData.author.username
                            }</div>
                            <div class="text-sm text-base-content/70">
                                À l'instant
                            </div>
                        </div>
                    </div>

                    <p class="mb-4 ">${postData.content}</p>

                    ${
                        postData.image
                            ? `
                        <div class="mb-4">
                            <img src="${postData.image}" alt="Image du post" class="rounded-lg max-w-full h-auto" />
                        </div>
                    `
                            : ""
                    }

                    <div class="card-actions justify-start">
                        <button class="btn btn-ghost btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                            J'aime
                        </button>
                        <button class="btn btn-ghost btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                            </svg>
                            Commenter
                        </button>
                        <button class="btn btn-ghost btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                            </svg>
                            Partager
                        </button>
                    </div>
                </div>
            </div>
        `;

        if (this.feedTarget) {
            this.feedTarget.insertAdjacentHTML("afterbegin", postHtml);

            this.showNotification(
                `Nouveau post de ${postData.author.username}!`
            );
        }
    }

    showNotification(message) {
        const notification = document.createElement("div");
        notification.className =
            "alert alert-success fixed top-4 right-4 w-auto z-50 animate-bounce-in";
        notification.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-6 w-6" fill="none" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>${message}</span>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
}
