(function () {
    const localHosts = ["localhost", "127.0.0.1", "::1"];
    const isLocal = localHosts.includes(window.location.hostname);
    const siteRoot = (() => {
        const script = document.querySelector('script[src*="home-content.js"]');
        if (!script) return new URL("./", window.location.href);

        const scriptPath = new URL(script.src, window.location.href).pathname;
        const rootPath = scriptPath.replace(/\/js\/home-content\.js$/i, "").replace(/\/$/, "");
        const pathParts = rootPath.split("/").filter(Boolean);

        // Algunos servidores locales exponen por error la ruta física del proyecto en la URL
        // (/Users/.../medicinadelalma/). En ese caso, usa solo la carpeta pública del sitio.
        const filesystemPreview = ["Users", "Volumes"].includes(pathParts[0]);
        const publicRoot = filesystemPreview && pathParts.length
            ? `/${pathParts[pathParts.length - 1]}/`
            : `${rootPath || ""}/`;

        return new URL(publicRoot, window.location.origin);
    })();
    const localSiteRoot = isLocal ? siteRoot : null;
    const endpoint = isLocal
        ? "http://127.0.0.1:8001/?api=home_content"
        : "https://campus.medicinadelalma.com.ar/?api=home_content";

    const make = (tag, className, value) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (value !== undefined) element.textContent = value;
        return element;
    };

    const normalizeSlug = (value) => value
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/([a-z0-9])([A-Z])/g, "$1-$2")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "-")
        .replace(/^-+|-+$/g, "");

    const workshopSlugFrom = (destination) => {
        if (!destination || destination.startsWith("#")) return null;
        let parsed;
        try {
            parsed = new URL(destination, siteRoot);
        } catch (_) {
            return null;
        }
        if (parsed.protocol !== "http:" && parsed.protocol !== "https:") return null;
        if (parsed.origin !== window.location.origin && /^https?:\/\//i.test(destination)) return null;

        const segments = decodeURIComponent(parsed.pathname).split("/").filter(Boolean);
        const workshopsIndex = segments.lastIndexOf("talleres");
        let candidate = workshopsIndex >= 0
            ? segments[workshopsIndex + 1]
            : segments[segments.length - 1];
        if (candidate && /^index\.html?$/i.test(candidate)) candidate = segments[segments.length - 2];
        if (!candidate || /\.html?$/i.test(candidate)) return null;
        return normalizeSlug(candidate);
    };

    const setDestination = (anchor, value, fallback, isWorkshop = false) => {
        const destination = value || fallback || "#contact";
        const workshopSlug = isWorkshop ? workshopSlugFrom(destination) : null;
        if (workshopSlug && isLocal) {
            const detailUrl = new URL("taller.html", localSiteRoot);
            detailUrl.searchParams.set("slug", workshopSlug);
            anchor.href = detailUrl.href;
        } else if (workshopSlug) {
            anchor.href = new URL(`talleres/${workshopSlug}/`, siteRoot).href;
        } else {
            anchor.setAttribute("href", destination);
        }
        if (/^https?:\/\//i.test(destination)) {
            anchor.target = "_blank";
            anchor.rel = "noopener noreferrer";
        }
    };

    function workshopCard(item) {
        const column = make("div", "col-md-4 center bottommargin-lg");
        const card = make("div", "feature-box media-box");
        const media = make("div", "fbox-media");
        media.style.padding = "0 40px";
        const imageLink = make("a");
        setDestination(imageLink, item.target_url, null, true);
        if (item.image_url) {
            const image = make("img", "rounded-circle img-thumbnail");
            image.src = item.image_url;
            image.alt = item.title || "";
            image.loading = "lazy";
            imageLink.append(image);
        }
        imageLink.append(make("span", "", item.title));
        media.append(imageLink);

        const description = make("div", "fbox-desc");
        const heading = make("h3");
        heading.append(make("span", "ls0 subtitle font-primary", item.title));
        description.append(heading, make("p", "", item.description || ""));
        const more = make("a", "more-link uppercase ls1 t700", item.link_label || "Saber más");
        more.style.margin = "20px 0 0";
        more.style.fontStyle = "normal";
        setDestination(more, item.target_url, null, true);

        card.append(media, description, more);
        column.append(card);
        return column;
    }

    function therapyCard(item) {
        const column = make("div", "col-lg-6 price-wrap");
        const header = make("div", "price-header");
        const name = make("div", "price-name");
        const link = make("a", "color", item.title);
        link.setAttribute("role", "button");
        link.setAttribute("aria-haspopup", "dialog");
        setDestination(link, item.target_url, "#contact");
        name.append(link);
        const dots = make("div", "price-dots");
        dots.append(make("span", "separator-dots"));
        header.append(name, dots);
        const description = make("p", "price-desc", item.description || "");
        description.style.textAlign = "justify";
        column.append(header, description);
        return column;
    }

    function objectCard(item) {
        const card = make("a", "product-card");
        setDestination(card, item.target_url, "#contact");
        card.setAttribute("aria-label", "Ver " + (item.title || "producto"));
        const media = make("span", "product-card__media");
        if (item.image_url) {
            const image = make("img");
            image.src = item.image_url;
            image.alt = item.title || "";
            image.loading = "lazy";
            media.append(image);
        }
        const body = make("span", "product-card__body");
        const isMercadoLibre = (item.target_url || "").includes("mercadolibre.");
        body.append(
            make("span", "product-card__eyebrow", isMercadoLibre ? "Mercado Libre" : "Medicina del Alma"),
            make("span", "product-card__title", item.title || ""),
            make("span", "product-card__note", item.description || "")
        );
        const action = make("span", "product-card__link");
        action.append(make("span", "", item.link_label || "Ver propuesta"), make("span", "", "↗"));
        action.lastChild.setAttribute("aria-hidden", "true");
        body.append(action);
        card.append(media, body);
        return card;
    }

    function replaceItems(list, items, renderer, sectionSelector, navigationSelector, rowClass, clearEvery) {
        const container = document.querySelector(list);
        if (!container || !Array.isArray(items)) return;
        container.replaceChildren();
        if (items.length && rowClass) {
            const row = make("div", rowClass);
            items.forEach((item) => row.append(renderer(item)));
            container.append(row);
        } else {
            items.forEach((item, index) => {
                container.append(renderer(item));
                if (clearEvery && (index + 1) % clearEvery === 0 && index < items.length - 1) {
                    container.append(make("div", "w-100 clear"));
                }
            });
        }
        const section = document.querySelector(sectionSelector);
        if (section) section.hidden = items.length === 0;
        const navLink = document.querySelector(navigationSelector);
        if (navLink) navLink.hidden = items.length === 0;
    }

    function showUnavailableMessage() {
        ["#home-workshops-content", "#home-therapies-content", "#home-products-content"].forEach((selector) => {
            const container = document.querySelector(selector);
            if (!container) return;
            const message = make("p", "home-content-status");
            message.append(document.createTextNode("Estamos actualizando nuestras propuestas. "));
            const contact = make("a", "", "Escríbenos y te orientamos.");
            contact.href = "#contact";
            message.append(contact);
            container.replaceChildren(message);
        });
    }

    fetch(endpoint, { cache: "no-store", credentials: "omit" })
        .then((response) => {
            if (!response.ok) throw new Error("No se pudo cargar el contenido administrable.");
            return response.json();
        })
        .then((content) => {
            if (!content || content.managed !== true) {
                showUnavailableMessage();
                return;
            }
            replaceItems("#home-workshops-content", content.workshops, workshopCard, "#talleres", '.site-nav a[href="#talleres"]', "row");
            replaceItems("#home-therapies-content", content.therapies, therapyCard, "#price", '.site-nav a[href="#price"]', null, 2);
            replaceItems("#home-products-content", content.objects, objectCard, "#shop", '.site-nav a[href="#shop"]');
        })
        .catch(() => {
            showUnavailableMessage();
        });
})();
