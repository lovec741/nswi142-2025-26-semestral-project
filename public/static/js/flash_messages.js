document.addEventListener("DOMContentLoaded", () => {
	const flashMessagesCont = document.getElementById("flash-messages-cont");

	function hideFlashMessages() {
		const childrenArray = Array.from(flashMessagesCont.children);
		childrenArray.forEach(flashMessageEl => {
			flashMessageEl.style.animationTimingFunction = "ease-in";
			flashMessageEl.style.animationName = "flashMessageAnimHide";
			flashMessageEl.style.pointerEvents = "none";
		});
	}

	const timeoutId = setTimeout(() => {
		hideFlashMessages();
	}, 5000);

	flashMessagesCont.addEventListener("click", () => {
		clearTimeout(timeoutId);
		hideFlashMessages();
	});
});