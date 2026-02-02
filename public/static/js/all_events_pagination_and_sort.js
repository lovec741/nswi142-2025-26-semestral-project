class View {
	constructor() {
		this.ascButton = document.getElementById('sort-start-date-asc');
		this.descButton = document.getElementById('sort-start-date-desc');
		this.hideCheckbox = document.getElementById('hide-past');
		this.nextButton = document.getElementById('page-next');
		this.prevButton = document.getElementById('page-prev');
		this.pageNumberText = document.getElementById('page-number');
		this.eventsContainer = document.getElementsByClassName('event-display-container')[0];
		this.unusedEventsContainer = document.getElementById('unused-event-displays-container');
	}

	setHandlers(ascHandler, descHandler, hideHandler, prevPageHandgler, nextPageHandler) {
		this.ascButton.onclick = ascHandler;
		this.descButton.onclick = descHandler;
		this.hideCheckbox.onchange = hideHandler;
		this.prevButton.onclick = prevPageHandgler;
		this.nextButton.onclick = nextPageHandler;
	}

	setPageNumberText(text) {
		this.pageNumberText.innerText = text;
	}

	showEventElements(shownElements) {
		const eventElements = this.getShownEventElements();
		console.log(eventElements);
		eventElements.forEach(element => {
			this.unusedEventsContainer.append(element);
		});
		shownElements.forEach(element => {
			this.eventsContainer.append(element);
		});
	}

	getShownEventElements() {
		return [...this.eventsContainer.children];
	}

	setDescButtonActive(active) {
		this.descButton.disabled = !active;
	}

	setAscButtonActive(active) {
		this.ascButton.disabled = !active;
	}

	setNextButtonActive(active) {
		this.nextButton.disabled = !active;
	}

	setPrevButtonActive(active) {
		this.prevButton.disabled = !active;
	}

	setHideCheckboxChecked(checked) {
		this.hideCheckbox.checked = checked;
	}
}

class Model {
	constructor() {
		this.pageNum = 1;
		this.loadStateFromLocalStorage();
	}

	loadStateFromLocalStorage() {
		this.sortDesc = (localStorage.getItem("sortDesc") ?? "false") === "true";
		this.hideOldEvents = (localStorage.getItem("hideOldEvents") ?? "true") === "true";
	}

	saveStateToLocalStorage() {
		localStorage.setItem("sortDesc", this.sortDesc);
		localStorage.setItem("hideOldEvents", this.hideOldEvents);
	}

	setEventElements(eventElements) {
		this.allEventElements = eventElements;
		this.newEventElements = eventElements.filter(v => { return !v.classList.contains("old"); });
	}

	setSort(desc) {
		this.sortDesc = desc;
		this.saveStateToLocalStorage();
	}

	isSortDesc() {
		return this.sortDesc;
	}

	setHideOldEvents(hide) {
		this.hideOldEvents = hide;
		this.saveStateToLocalStorage();
	}

	areOldEventsHidden() {
		return this.hideOldEvents;
	}

	incrementPage(incrementBy) {
		this.pageNum += incrementBy;
	}

	isPrevPage() {
		return this.pageNum !== 1;
	}

	getCurrentPageNum() {
		return this.pageNum;
	}

	getLastPageNum() {
		return Math.ceil((this.hideOldEvents ? this.newEventElements.length : this.allEventElements.length) / EVENTS_PER_PAGE);
	}

	isNextPage() {
		return this.pageNum < this.getLastPageNum();
	}

	getEventElements() {
		let elements = this.hideOldEvents ? this.newEventElements.slice() : this.allEventElements.slice();
		if (this.sortDesc) {
			elements = elements.reverse();
		}
		return elements.slice((this.pageNum - 1) * EVENTS_PER_PAGE, this.pageNum * EVENTS_PER_PAGE);
	}
}

class Presenter {
	constructor(view, model) {
		this.view = view;
		this.model = model;
	}

	init() {
		this.model.setEventElements(this.view.getShownEventElements());
		this.view.setHandlers(
			this.onAscClick.bind(this),
			this.onDescClick.bind(this),
			this.onHideClick.bind(this),
			this.onPrevClick.bind(this),
			this.onNextClick.bind(this)
		);
		this.updatePage();
		this.view.setHideCheckboxChecked(this.model.areOldEventsHidden());
	}

	updatePage() {
		this.view.showEventElements(this.model.getEventElements());
		this.view.setPageNumberText(`${this.model.getCurrentPageNum()}/${this.model.getLastPageNum()}`);
		this.view.setPrevButtonActive(this.model.isPrevPage());
		this.view.setNextButtonActive(this.model.isNextPage());
		this.view.setAscButtonActive(this.model.isSortDesc());
		this.view.setDescButtonActive(!this.model.isSortDesc());
		this.view.setDescButtonActive(!this.model.isSortDesc());

	}

	onHideClick(e) {
		this.model.setHideOldEvents(e.target.checked);
		this.updatePage();
	}

	onDescClick(e) {
		this.model.setSort(true);
		this.updatePage();
	}

	onAscClick(e) {
		this.model.setSort(false);
		this.updatePage();
	}

	onNextClick(e) {
		this.model.incrementPage(1);
		this.updatePage();
	}

	onPrevClick(e) {
		this.model.incrementPage(-1);
		this.updatePage();
	}
}

document.addEventListener("DOMContentLoaded", () => {
	let view = new View();
	let model = new Model();
	let presenter = new Presenter(view, model);
	presenter.init();
});