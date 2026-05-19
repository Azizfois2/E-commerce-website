class AllowAccessSection extends BaseComponent {
	/**
	 * @param {Object} props
	 * @param {string} props.title
	 * @param {string} props.description
	 * @param {void} props.onButtonClick
	 * @param {boolean} props.isVisible
	 */
	constructor(props) {
		super(props);

		this.render();
	}

	componentDidUpdateProps(key, prevValue) {
		if (key === "isVisible" && this.props.isVisible !== prevValue) {
			this.setVisibility();
		}
	}

	setVisibility() {
		if (this.props.isVisible === false) {
			this.element.classList.add("allow-access-section_hidden");
		} else {
			this.element.classList.remove("allow-access-section_hidden");
		}
	}

	render() {
		this.element = document.createElement("div");
		this.element.className = "allow-access-section";
		this.setVisibility();

		const mainContent = document.createElement("div");

		const header = document.createElement("div");
		header.className = "allow-access-section__header";

		const infoIcon = new Icon({
			href: "../../assets/icons.svg#info-icon",
			className: "allow-access-section__icon"
		});

		const title = document.createElement("h1");
		title.className = "allow-access-section__title";
		title.innerText = this.props.title;

		header.appendChild(infoIcon.node);
		header.appendChild(title);

		const description = document.createElement("div");
		description.className = "allow-access-section__description";

		if (typeof this.props.description === "string") {
			description.innerText = this.props.description;
		} else if (Array.isArray(this.props.description)) {
			this.props.description.forEach((item) => {
				const paragraph = document.createElement("p");
				paragraph.className = "allow-access-section__description-paragraph";
				paragraph.innerText = item;
				description.appendChild(paragraph);
			});
		}

		mainContent.appendChild(header);
		mainContent.appendChild(description);

		const button = new Button({
			title: chrome.i18n.getMessage("info_page_allow_access_button"),
			onClick: this.props.onButtonClick
		});

		this.element.appendChild(mainContent);
		this.element.appendChild(button.node);
		this.node.appendChild(this.element);
	}
}
