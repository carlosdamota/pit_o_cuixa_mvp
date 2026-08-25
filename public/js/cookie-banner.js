class CookieBanner {

    constructor() {
    this.banner = document.getElementById("cookie-banner");
    this.settingsButtons = document.querySelectorAll("[data-cookie-settings]");
    this.acceptButton = document.querySelector(".cookie-banner__button--accept");
    this.rejectButton = document.querySelector(".cookie-banner__button--reject");
    this.necessaryButton = document.querySelector(".cookie-banner__button--necessary");
    this.storageKey = "pitocuixa_cookie_preferences";
    this.cookieCategories = {
        necessary: true,
        analytics: false
    };
    this.chicken = document.querySelector(".cookie-banner__chicken");
    this.closeButton =
    document.querySelector(".cookie-banner__close");
    this.openedFromSettings = false;
    }

    init() {

        if (!this.banner) {
            return;
        }

        this.checkPreferences();

        this.moveChicken(this.necessaryButton);

        this.acceptButton.addEventListener("click", () => {
            this.selectButton(this.acceptButton);
            this.savePreferences("accept");
        });

        this.necessaryButton.addEventListener("click", () => {
            this.selectButton(this.necessaryButton);
            this.savePreferences("necessary");
        });

        this.rejectButton.addEventListener("click", () => {
            this.selectButton(this.rejectButton);
            this.savePreferences("reject");
        });

        this.acceptButton.addEventListener("mouseenter", () => {
            this.moveChicken(this.acceptButton);
        });

        this.rejectButton.addEventListener("mouseenter", () => {
            this.moveChicken(this.rejectButton);
        });

        this.necessaryButton.addEventListener("mouseenter", () => {
            this.moveChicken(this.necessaryButton);
        });

        [
            this.acceptButton,
            this.necessaryButton,
            this.rejectButton
        ].forEach((button) => {

        button.addEventListener("mouseenter", () => {
            this.moveChicken(button);
        });

        button.addEventListener("focus", () => {
            this.moveChicken(button);
        });

        button.addEventListener("touchstart", () => {
            this.moveChicken(button);
        });

        });

        this.settingsButtons.forEach((button) => {
            button.addEventListener("click", () => {
                this.openedFromSettings = true;
                this.show();
            });
        });

        this.closeButton.addEventListener("click", () => {

            if (this.openedFromSettings) {
                this.hide();
            }

        });

        document.addEventListener("keydown", (event) => {

            if (event.key !== "Escape") {
                return;
            }

            if (!this.openedFromSettings) {
                return;
            }

            if (this.banner.hidden) {
                return;
            }

            this.hide();

        });

    }

    checkPreferences() {
        const storedPreferences = localStorage.getItem(this.storageKey);

        if (!storedPreferences) {
            this.openedFromSettings = false;
            this.show();
            return;
        }

        try {
            const storedPreferences = localStorage.getItem(this.storageKey);

            if (!storedPreferences) {
                this.openedFromSettings = false;
                this.show();
                return;
            }

            this.loadPreferences();

            console.log("Preferencias detectadas:", this.cookieCategories);
            this.hide();

        } catch (error) {
            console.error("Error leyendo las preferencias de cookies:", error);

            // Si el dato guardado está corrupto,
            // volvemos a mostrar el banner.
            localStorage.removeItem(this.storageKey);

            this.openedFromSettings = false;
            this.show();
        }
    }

    show() {

        this.banner.hidden = false;

        if (this.openedFromSettings) {

            this.closeButton.hidden = false;

        } else {

            this.closeButton.hidden = true;

        }

        requestAnimationFrame(() => {
            this.banner.classList.add("cookie-banner--visible");
        });

        this.settingsButtons.forEach((button) => {
            button.hidden = true;
        });

        const storedPreferences = localStorage.getItem(this.storageKey);

        if (!storedPreferences) {
            return;
        }

        try {
            const preferences = JSON.parse(storedPreferences);

            switch (preferences.choice) {

                case "accept":
                    this.moveChicken(this.acceptButton);
                    this.selectButton(this.acceptButton);
                    break;

                case "necessary":
                    this.moveChicken(this.necessaryButton);
                    this.selectButton(this.necessaryButton);
                    break;

                case "reject":
                    this.moveChicken(this.rejectButton);
                    this.selectButton(this.rejectButton);
                    break;

            }

        } catch (error) {

            console.error("Error leyendo las preferencias:", error);
        }
    }

    hide() {

        this.banner.classList.remove("cookie-banner--visible");

        setTimeout(() => {
            this.banner.hidden = true;
            this.settingsButtons.forEach((button) => {
                button.hidden = false;
            });
        }, 300);

        this.openedFromSettings = false;

    }

    moveChicken(button) {

    const buttonRect = button.getBoundingClientRect();
    const bodyRect = this.chicken.parentElement.getBoundingClientRect();

    const chickenWidth = this.chicken.offsetWidth;

    const x =
        buttonRect.left
        - bodyRect.left
        + (buttonRect.width / 2)
        - (chickenWidth / 2)
        + 25;

    this.chicken.style.left = `${x}px`;
    this.chicken.style.transform = "translateX(0)";

    }

    savePreferences(option) {
        let preferences;
        
        switch (option) {

            case "accept":
                preferences = {
                    necessary: true,
                    analytics: true,
                    choice: "accept"
                };
                break;

            case "necessary":
                preferences = {
                    necessary: true,
                    analytics: false,
                    choice: "necessary"
                };
                break;

            case "reject":
                preferences = {
                    necessary: true,
                    analytics: false,
                    choice: "reject"
                };
                break;

            default:
                return;
        }

        console.log("Preferencias guardadas:", preferences);

        this.cookieCategories = {
            necessary: preferences.necessary,
            analytics: preferences.analytics
        };

        localStorage.setItem(
            this.storageKey,
            JSON.stringify(preferences)
        );

        this.hide();

        console.log(
            "Analytics permitido:",
            this.hasConsent("analytics")
        );
    }

    hasConsent(category) {

        return this.cookieCategories[category] === true;

    }

    getPreferences() {
        const storedPreferences = localStorage.getItem(this.storageKey);

        if (!storedPreferences) {
            return null;
        }

        try {
            return JSON.parse(storedPreferences);

        } catch (error) {
            console.error("Error leyendo las preferencias:", error);
            return null;
        }
    }

    loadPreferences() {
        const preferences = this.getPreferences();

        if (!preferences) {
            return;
        }

        this.cookieCategories = {
            necessary: true,
            analytics: preferences.analytics === true
        };
    }

    clearSelectedButtons() {

    [
        this.acceptButton,
        this.necessaryButton,
        this.rejectButton
    ].forEach((button) => {

        button.classList.remove("cookie-banner__button--selected");

    });

    }

    selectButton(button) {

        this.clearSelectedButtons();

        button.classList.add("cookie-banner__button--selected");

    }

}

let cookieBannerInstance = null;

export function initCookieBanner() {
    cookieBannerInstance = new CookieBanner();
    cookieBannerInstance.init();
}

export function getCookieBanner() {

    return cookieBannerInstance;

}