import { Controller } from "@hotwired/stimulus";

export default class extends Controller {
    static targets = ["input", "preview", "error"];
    static values = {
        maxSize: { type: Number, default: 2 * 1024 * 1024 }, // 2MB en bytes
        allowedTypes: {
            type: Array,
            default: ["image/jpeg", "image/png", "image/webp", "image/gif"],
        },
    };

    connect() {
        console.log("🖼️ Image upload controller connecté");
    }

    validateFile(event) {
        const file = event.target.files[0];

        if (!file) {
            this.clearPreview();
            return;
        }

        // Validation de la taille
        if (file.size > this.maxSizeValue) {
            this.showError(
                `L'image est trop volumineuse. Taille maximum: ${this.formatFileSize(
                    this.maxSizeValue
                )}`
            );
            this.clearInput();
            return;
        }

        // Validation du type de fichier
        if (!this.allowedTypesValue.includes(file.type)) {
            this.showError(
                `Type de fichier non supporté. Types autorisés: ${this.allowedTypesValue.join(
                    ", "
                )}`
            );
            this.clearInput();
            return;
        }

        // Validation de la résolution (optionnel)
        this.validateImageDimensions(file);

        this.hideError();
        this.showPreview(file);
    }

    validateImageDimensions(file) {
        const img = new Image();
        const url = URL.createObjectURL(file);

        img.onload = () => {
            URL.revokeObjectURL(url);

            // Vérifier si l'image est trop grande (ex: max 4000x4000)
            if (img.width > 4000 || img.height > 4000) {
                this.showError(
                    `Image trop grande: ${img.width}x${img.height}px. Maximum: 4000x4000px`
                );
                this.clearInput();
                return;
            }

            console.log(
                `✅ Image validée: ${img.width}x${
                    img.height
                }px, ${this.formatFileSize(file.size)}`
            );
        };

        img.src = url;
    }

    showPreview(file) {
        if (this.hasPreviewTarget) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewTarget.innerHTML = `
                    <div class="relative">
                        <img src="${e.target.result}" class="max-w-full h-32 object-cover rounded-lg border" alt="Aperçu">
                        <button type="button" class="absolute top-1 right-1 btn btn-circle btn-xs btn-error" data-action="click->image-upload#removePreview">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                `;
                this.previewTarget.classList.remove("hidden");
            };
            reader.readAsDataURL(file);
        }
    }

    removePreview() {
        this.clearPreview();
        this.clearInput();
    }

    clearPreview() {
        if (this.hasPreviewTarget) {
            this.previewTarget.innerHTML = "";
            this.previewTarget.classList.add("hidden");
        }
    }

    clearInput() {
        this.inputTarget.value = "";
    }

    showError(message) {
        if (this.hasErrorTarget) {
            this.errorTarget.textContent = message;
            this.errorTarget.classList.remove("hidden");
        } else {
            // Fallback: afficher l'erreur dans une alert
            alert(message);
        }
    }

    hideError() {
        if (this.hasErrorTarget) {
            this.errorTarget.classList.add("hidden");
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return "0 Bytes";
        const k = 1024;
        const sizes = ["Bytes", "KB", "MB", "GB"];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + " " + sizes[i];
    }
}
