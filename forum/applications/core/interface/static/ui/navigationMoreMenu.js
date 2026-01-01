if (!window.customElements.get('i-navigation-menu')) {
	class INavigationMenu extends HTMLElement {

		constructor () {
			super();
			this.menu = this.querySelector('[data-role="menu"]');
			this.moreLi = this.menu.querySelector('[data-role="moreLi"]');
			this.moreMenu = this.moreLi.querySelector('[data-role="moreMenu"]');
			this.clone = this.querySelector('[data-role="clone"]');
		}

		moveToMore(links){
			const moreFragment = document.createDocumentFragment();
			links.forEach(link => {
				// Move link into fragment so we can batch prepend later
				moreFragment.append(link);
				// See if a dropdown exists and if so, remove light-dismiss
				let mainDropdown = link.querySelector(".ipsNav__dropdown");
				if(mainDropdown){
					mainDropdown.removeAttribute('data-ips-hidden-light-dismiss');
					mainDropdown.setAttribute('data-ips-hidden-light-dismiss-disabled', '');
				}
			});
			// If the more menu contains the active link..
			if(moreFragment.querySelector("[aria-current]")){
				// ..add [data-active] to "More"
				this.moreLi.setAttribute("data-active", "");			
				// ..and expand the active dropdown
				moreFragment.querySelectorAll(':scope > [data-active]').forEach(el => {
					const button = el.querySelector(":scope > [aria-expanded]");
					if(button){
						button.setAttribute("aria-expanded", "true");
					}
					const menu = el.querySelector(":scope > .ipsNav__dropdown");
					if(menu){
						menu.hidden = false;
					}
				});
			}
			// Add links to More menu
			this.moreMenu.prepend(moreFragment);
		}

		moveToMain(links){
			const mainFragment = document.createDocumentFragment();
			links.forEach(link => {
				// Move link into fragment so we can batch prepend later
				mainFragment.append(link);
				// See if a dropdown exists and if so, add light-dismiss
				let mainDropdown = link.querySelector(".ipsNav__dropdown");
				if(mainDropdown){
					mainDropdown.removeAttribute('data-ips-hidden-light-dismiss-disabled');
					mainDropdown.setAttribute('data-ips-hidden-light-dismiss', '');
				}
			});
			// Move links into Main menu
			this.moreLi.before(mainFragment);
			// Remove [data-active] from the More menu
			if(!this.moreMenu.querySelector("[aria-current]")){
				this.moreLi.removeAttribute("data-active");
			}
		}

		// IntersectionObserver for More menu
		priorityObserver = new IntersectionObserver((entries) => {
			let moveLinksToMain = [],
				moveLinksToMore = [];
			entries.forEach((entry) => {
				if(entry.isIntersecting){
					// Get the actual navigation link
					const link = this.moreMenu.querySelector(`[data-id="${entry.target.dataset.id}"]`);
					// If the link is already in the main menu, ignore it
					if(!link) return;
					// Add it to the array so we can append it later
					moveLinksToMain.push(link);
				} else {
					// Get the actual navigation link
					const link = this.menu.querySelector(`[data-id="${entry.target.dataset.id}"]`);
					if(!link) return;
					// If the link is already in the more menu, ignore it
					if(link.closest(".ipsNav__dropdown")) return;
					// Add it to the array so we can prepend it later
					moveLinksToMore.push(link);
				}
			});
			if(moveLinksToMain.length){
				this.moveToMain(moveLinksToMain);
			}else if(moveLinksToMore.length){
				this.moveToMore(moveLinksToMore);
			}
			// Store the first More link, so we can hide it on page load with cookies to prevent Layout Shifts
			let firstMoreLink = this.moreMenu.firstElementChild;
			// Hide/show More menu depending on its children
			this.moreLi.hidden = !firstMoreLink;
			if(firstMoreLink){
				ips.utils.cookie.set("moreMenuLink", firstMoreLink.dataset.id, false);
			} else {
				ips.utils.cookie.unset("moreMenuLink");
			}
			this.setAttribute("data-observed", "");
		}, {
			root: this.querySelector(".js-ipsNavPriority"),
			threshold: 1
		})

		connectedCallback(){
			// Add IntersectionObserver to links
			this.items = this.clone.querySelectorAll(":scope > :not([data-role='moreLiClone'])");
			this.items.forEach(i => this.priorityObserver.observe(i));
		}
	};

	window.customElements.define('i-navigation-menu', INavigationMenu);
}