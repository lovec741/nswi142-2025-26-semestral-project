document.addEventListener("DOMContentLoaded", () => {
	let tommorowDate = new Date();
	tommorowDate.setDate(tommorowDate.getDate() + 1);
	const minDateStr = tommorowDate.toISOString().split('T')[0];
	const startDateEl = document.getElementById("startDate");
	const endDateEl = document.getElementById("endDate");
	startDateEl.min = minDateStr;
	endDateEl.min = minDateStr;

	const addWorkshopButtonEl = document.getElementById("add-workshop-button");
	const workshopContainerEl = document.getElementById("workshop-container");
	addWorkshopButtonEl.addEventListener("click", () => {
		const newWorkshopInputEl = document.createElement("input");
		newWorkshopInputEl.type = "text";
		newWorkshopInputEl.name = "workshop[]";
		newWorkshopInputEl.required = "required";
		const newWorkshopRemoveButtonEl = document.createElement("button");
		newWorkshopRemoveButtonEl.type = "button";
		newWorkshopRemoveButtonEl.className = "btn btn-sm";
		newWorkshopRemoveButtonEl.textContent = "x Remove";
		newWorkshopRemoveButtonEl.addEventListener("click", e => {
			e.target.parentElement.remove();
		});
		const newWorkshopContainerDiv = document.createElement("div");
		newWorkshopContainerDiv.className = "inner-workshop-container";
		newWorkshopContainerDiv.append(newWorkshopInputEl, newWorkshopRemoveButtonEl);
		workshopContainerEl.appendChild(newWorkshopContainerDiv);
	})
});
