class AllSetSection extends BaseComponent {
	/**
	 * @param {Object} props
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
			this.element.classList.add("main-content__section_hidden");
		} else {
			this.element.classList.remove("main-content__section_hidden");
		}
	}

	render() {
		this.element = document.createElement("div");
		this.element.className = "main-content__section main-content__all-set";
		this.setVisibility();

		const infoIcon = new Icon({
			href: "../../assets/icons.svg#success",
			className: "main-content__icon"
		});

		const title = document.createElement("h1");
		title.className = "main-content__title";
		title.innerText = chrome.i18n.getMessage("website_security_inspector_setup_all_set_title");

		const infoText = document.createElement("div");
		infoText.className = "main-content__info-text";
		getDomElementsFromString(chrome.i18n.getMessage("website_security_inspector_setup_all_set_info")).forEach(
			(element) => {
				infoText.appendChild(element);
			}
		);

		this.element.appendChild(infoIcon.node);
		this.element.appendChild(title);
		this.element.appendChild(infoText);
		this.node.appendChild(this.element);
	}
}
