class CookieBanner {

    constructor() {
    this.banner = document.getElementById("cookie-banner");
    this.settingsButtons = document.querySelectorAll("[data-cookie-settings]");
    this.acceptButton = document.querySelector(".cookie-banner__button--accept");
    this.rejectButton = document.querySelector(".cookie-banner__button--reject");
    this.necessaryButton = document.querySelector(".cookie-banner__button--necessary");
    this.storageKey = "pitocuixa_cookie_preferences";
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

        const preferences = localStorage.getItem(this.storageKey);

        if (preferences) {
            this.hide();
            return;
        }

        this.openedFromSettings = false;
        this.show();
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

        const preference = localStorage.getItem(this.storageKey);

        switch (preference) {

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
        localStorage.setItem(this.storageKey, option);
        this.hide();
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

export function initCookieBanner() {

    const cookieBanner = new CookieBanner();

    cookieBanner.init();

}