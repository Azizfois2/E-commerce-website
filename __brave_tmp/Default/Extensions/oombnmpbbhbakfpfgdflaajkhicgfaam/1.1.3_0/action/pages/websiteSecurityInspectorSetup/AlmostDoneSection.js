class AlmostDoneSection extends BaseComponent {
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
		this.element.className = "main-content__section main-content__almost-done";
		this.setVisibility();

		const infoIcon = new Icon({
			href: "../../assets/icons.svg#info-square",
			className: "main-content__icon"
		});

		const title = document.createElement("h1");
		title.className = "main-content__title";
		title.innerText = chrome.i18n.getMessage("website_security_inspector_setup_almost_done_title");

		const infoText = document.createElement("div");
		infoText.className = "main-content__info-text";
		getDomElementsFromString(chrome.i18n.getMessage("website_security_inspector_setup_almost_done_info")).forEach(
			(element) => {
				infoText.appendChild(element);
			}
		);

		const subtitle = document.createElement("h2");
		subtitle.className = "main-content__subtitle";
		subtitle.innerText = chrome.i18n.getMessage("website_security_inspector_setup_almost_done_subtitle");

		const allowAccessSection = new AllowAccessSection({
			title: chrome.i18n.getMessage("welcome_page_allow_access_section_title"),
			description: [
				chrome.i18n.getMessage("website_security_inspector_setup_allow_access_section_description_1"),
				chrome.i18n.getMessage("website_security_inspector_setup_allow_access_section_description_2")
			],
			isVisible: true,
			onButtonClick: async () => {
				chrome.runtime.sendMessage({
					msg: "wsi_ask_permissions"
				});

				const isPermissionGranted = await chrome.permissions.request({ origins: [AllUrls] });

				if (isPermissionGranted) {
					chrome.runtime.sendMessage({
						msg: "wsi_grant_permissions"
					});
				}
			}
		});

		this.element.appendChild(infoIcon.node);
		this.element.appendChild(title);
		this.element.appendChild(infoText);
		this.element.appendChild(subtitle);
		this.element.appendChild(allowAccessSection.node);
		this.node.appendChild(this.element);
	}
}
