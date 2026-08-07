<!-- ============================================================
     Cookie Banner Component
     ============================================================ -->

<div id="cookie-banner" class="cookie-banner" hidden>

    <!-- Fondo oscuro -->
    <div class="cookie-banner__backdrop"></div>

    <!-- Contenedor -->
    <div class="cookie-banner__container">

        <button
            class="cookie-banner__close"
            aria-label="Cerrar"
            hidden>

            ✕

        </button>

        <!-- Cabecera -->
        <div class="cookie-banner__header">

            <div class="cookie-banner__icon">
                <img
                    src="/img/icons/galleta.png"
                    alt=""
                    width="34"
                    height="34">
            </div>

            <div class="cookie-banner__title-group">

                <h2 class="cookie-banner__title">
                    Cookies y privacidad
                </h2>

                <p class="cookie-banner__subtitle">
                    Utilizamos cookies necesarias para el correcto funcionamiento de la web. También puedes aceptar cookies analíticas para ayudarnos a mejorar la experiencia.
                </p>

            </div>

        </div>

        <!-- Contenido -->
        <div class="cookie-banner__body">

            <!-- Aquí irá la gallina -->
            <div class="cookie-banner__chicken">
                <img
                    id="cookie-chicken"
                    src="/img/icons/favicon.png"
                    alt="Gallina">
            </div>

        </div>

        <!-- Botones -->
        <div class="cookie-banner__actions">

            <button
                class="cookie-banner__button cookie-banner__button--reject">
                Rechazar
            </button>

            <button
                class="cookie-banner__button cookie-banner__button--necessary">
                Solo necesarias
            </button>

            <button
                class="cookie-banner__button cookie-banner__button--accept">
                Aceptar todas
            </button>

        </div>

        <!-- Links -->
        <div class="cookie-banner__links">

            <a href="/cookies">
                Política de Cookies
            </a>

            <span>•</span>

            <a href="/privacy">
                Política de Privacidad
            </a>

        </div>

    </div>

</div>

