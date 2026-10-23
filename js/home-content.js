(function () {
    const localHosts = ["localhost", "127.0.0.1", "::1"];
    const isLocal = localHosts.includes(window.location.hostname);
    const endpoint = isLocal
        ? "http://127.0.0.1:8001/?api=home_content"
        : "https://campus.medicinadelalma.com.ar/?api=home_content";

    const make = (tag, className, value) => {
        const element = document.createElement(tag);
        if (className) element.className = className;
        if (value !== undefined) element.textContent = value;
        return element;
    };

    const setDestination = (anchor, value, fallback) => {
        const destination = value || fallback || "#contact";
        anchor.setAttribute("href", destination);
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
        setDestination(imageLink, item.target_url);
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
        setDestination(more, item.target_url);

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
