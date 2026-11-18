const themeToggles = document.querySelectorAll(".theme-toggle");
const reducedMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;

function applyTheme(theme) {
    document.documentElement.dataset.theme = theme;

    const isDark = theme === "dark";
    themeToggles.forEach((btn) => {
        btn.setAttribute("aria-pressed", String(isDark));
        btn.setAttribute(
            "aria-label",
            isDark ? "Switch to light mode" : "Switch to dark mode"
        );
        const icon = btn.querySelector(".theme-toggle-icon");
        if (icon) {
            icon.textContent = isDark ? "☀" : "☾";
        }
    });

    const themeColor = document.querySelector('meta[name="theme-color"]');
    if (themeColor) {
        const isAdminDashboard = document.body && document.body.classList.contains("admin-body");
        themeColor.setAttribute(
            "content",
            theme === "dark" ? "#0C0C16" : (isAdminDashboard ? "#f4f6f9" : "#EDE8F8")
        );
    }

    const cookiePath = document.documentElement.dataset.themeCookiePath || "/";
    document.cookie =
        `campus-theme=${theme}; Max-Age=31536000; Path=${cookiePath}; SameSite=Lax`;
}

function syncThemeToggle(theme) {
    const isDark = theme === "dark";
    themeToggles.forEach((btn) => {
        btn.setAttribute("aria-pressed", String(isDark));
        btn.setAttribute(
            "aria-label",
            isDark ? "Switch to light mode" : "Switch to dark mode"
        );
        const icon = btn.querySelector(".theme-toggle-icon");
        if (icon) {
            icon.textContent = isDark ? "☀" : "☾";
        }
    });
}

if (document.querySelector(".student-dashboard")) {
    document.body.classList.add("is-dashboard-page");
}

syncThemeToggle(document.documentElement.dataset.theme || "light");

document.querySelectorAll(".navbar-menu").forEach((menu) => {
    document.addEventListener("pointerdown", (event) => {
        if (menu.open && !menu.contains(event.target)) {
            menu.open = false;
        }
    });

    menu.addEventListener("keydown", (event) => {
        if (event.key === "Escape" && menu.open) {
            event.preventDefault();
            menu.open = false;
            menu.querySelector("summary")?.focus();
        }
    });
});

themeToggles.forEach((btn) => {
    btn.addEventListener("click", () => {
        const nextTheme =
            document.documentElement.dataset.theme === "dark" ? "light" : "dark";
        applyTheme(nextTheme);
    });
});

const passwordEyeOpen = `
    <svg class="password-eye-open" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round"
         stroke-linejoin="round" aria-hidden="true">
        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"></path>
        <circle cx="12" cy="12" r="3"></circle>
    </svg>`;
const passwordEyeClosed = `
    <svg class="password-eye-closed" viewBox="0 0 24 24" fill="none"
         stroke="currentColor" stroke-width="2" stroke-linecap="round"
         stroke-linejoin="round" aria-hidden="true">
        <path d="M3 3l18 18"></path>
        <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"></path>
        <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-4 4.8"></path>
        <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.1 0 2.1-.2 3-.5"></path>
    </svg>`;

document.querySelectorAll("[data-password-toggle]").forEach((button) => {
    const input = document.getElementById(button.dataset.passwordToggle);
    if (!(input instanceof HTMLInputElement) || button.dataset.passwordInitialized) {
        return;
    }

    button.dataset.passwordInitialized = "true";
    button.classList.add("password-visibility-toggle");
    const label = (button.getAttribute("aria-label") || "Show password")
        .replace(/^(show|hide)\s+/i, "")
        .trim() || "password";
    const iconContainer = document.createElement("span");
    iconContainer.className = "password-eye-icons";
    iconContainer.innerHTML = `${passwordEyeOpen}${passwordEyeClosed}`;
    button.replaceChildren(iconContainer);

    const updateVisibility = (isVisible) => {
        input.type = isVisible ? "text" : "password";
        button.classList.toggle("is-visible", isVisible);
        button.setAttribute("aria-pressed", String(isVisible));
        button.setAttribute("aria-label", `${isVisible ? "Hide" : "Show"} ${label}`);
        button.title = `${isVisible ? "Hide" : "Show"} ${label}`;
    };

    button.addEventListener("click", () => {
        updateVisibility(input.type === "password");
    });

    updateVisibility(input.type !== "password");
});

const parseCalendarDate = (value) => {
    const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value || "");
    if (!match) {
        return null;
    }

    const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
    return date.getFullYear() === Number(match[1]) &&
        date.getMonth() === Number(match[2]) - 1 &&
        date.getDate() === Number(match[3])
        ? date
        : null;
};

const formatCalendarDate = (date) => {
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${date.getFullYear()}-${month}-${day}`;
};

document.querySelectorAll('input[type="date"]').forEach((input, index) => {
    const wrapper = document.createElement("div");
    wrapper.className = "calendar-field";
    const toggle = document.createElement("button");
    const popover = document.createElement("div");
    const popoverId = `calendar-popover-${index + 1}`;
    const titleId = `calendar-title-${index + 1}`;
    const inputLabel = input.labels && input.labels.length
        ? input.labels[0].textContent.trim()
        : "Select a date";
    let displayedMonth = parseCalendarDate(input.value) || new Date();
    displayedMonth = new Date(displayedMonth.getFullYear(), displayedMonth.getMonth(), 1);

    toggle.type = "button";
    toggle.className = "calendar-toggle";
    toggle.setAttribute("aria-label", `Open calendar for ${inputLabel}`);
    toggle.setAttribute("aria-haspopup", "dialog");
    toggle.setAttribute("aria-expanded", "false");
    toggle.setAttribute("aria-controls", popoverId);
    toggle.innerHTML = `
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
             stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
            <rect x="3" y="5" width="18" height="16" rx="3"></rect>
            <path d="M16 3v4M8 3v4M3 10h18"></path>
            <path d="M8 14h.01M12 14h.01M16 14h.01M8 17h.01M12 17h.01"></path>
        </svg>`;

    popover.id = popoverId;
    popover.className = "calendar-popover";
    popover.setAttribute("role", "dialog");
    popover.setAttribute("aria-label", `${inputLabel} calendar`);
    popover.hidden = true;
    popover.inert = true;

    input.parentNode.insertBefore(wrapper, input);
    wrapper.append(input, toggle, popover);
    input.classList.add("calendar-date-input");
    input.setAttribute("aria-haspopup", "dialog");
    input.setAttribute("aria-controls", popoverId);

    const minDate = () => parseCalendarDate(input.min);
    const maxDate = () => parseCalendarDate(input.max);
    const isAllowedDate = (date) => {
        const minimum = minDate();
        const maximum = maxDate();
        return (!minimum || date >= minimum) && (!maximum || date <= maximum);
    };
    const monthFormatter = new Intl.DateTimeFormat(undefined, {
        month: "long",
        year: "numeric"
    });
    const dateFormatter = new Intl.DateTimeFormat(undefined, {
        weekday: "long",
        month: "long",
        day: "numeric",
        year: "numeric"
    });
    const weekdayFormatter = new Intl.DateTimeFormat(undefined, { weekday: "short" });

    function renderCalendar(focusDate) {
        const selectedDate = parseCalendarDate(input.value);
        const today = new Date();
        const todayValue = formatCalendarDate(today);
        const header = document.createElement("div");
        header.className = "calendar-header";

        const previous = document.createElement("button");
        previous.type = "button";
        previous.className = "calendar-month-button";
        previous.setAttribute("aria-label", "Previous month");
        previous.dataset.calendarPrevious = "";
        previous.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"></path></svg>';

        const monthTitle = document.createElement("strong");
        monthTitle.id = titleId;
        monthTitle.className = "calendar-month-title";
        monthTitle.textContent = monthFormatter.format(displayedMonth);

        const next = document.createElement("button");
        next.type = "button";
        next.className = "calendar-month-button";
        next.setAttribute("aria-label", "Next month");
        next.dataset.calendarNext = "";
        next.innerHTML = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"></path></svg>';

        const minimum = minDate();
        const maximum = maxDate();
        const previousMonthEnd = new Date(
            displayedMonth.getFullYear(),
            displayedMonth.getMonth(),
            0
        );
        const nextMonthStart = new Date(
            displayedMonth.getFullYear(),
            displayedMonth.getMonth() + 1,
            1
        );
        previous.disabled = Boolean(minimum && previousMonthEnd < minimum);
        next.disabled = Boolean(maximum && nextMonthStart > maximum);
        header.append(previous, monthTitle, next);

        const weekdays = document.createElement("div");
        weekdays.className = "calendar-weekdays";
        weekdays.setAttribute("aria-hidden", "true");
        for (let day = 0; day < 7; day += 1) {
            const weekday = document.createElement("span");
            weekday.textContent = weekdayFormatter.format(new Date(2023, 0, day + 1));
            weekdays.append(weekday);
        }

        const days = document.createElement("div");
        days.className = "calendar-days";
        const firstWeekday = new Date(
            displayedMonth.getFullYear(),
            displayedMonth.getMonth(),
            1
        ).getDay();

        for (let index = 0; index < 42; index += 1) {
            const date = new Date(
                displayedMonth.getFullYear(),
                displayedMonth.getMonth(),
                index - firstWeekday + 1
            );
            const dateValue = formatCalendarDate(date);
            const day = document.createElement("button");
            day.type = "button";
            day.className = "calendar-day";
            day.dataset.calendarDate = dateValue;
            day.textContent = String(date.getDate());
            day.setAttribute("aria-label", dateFormatter.format(date));
            day.setAttribute("aria-pressed", String(dateValue === input.value));
            day.disabled = !isAllowedDate(date);

            if (date.getMonth() !== displayedMonth.getMonth()) {
                day.classList.add("is-outside-month");
            }
            if (dateValue === todayValue) {
                day.classList.add("is-today");
                day.setAttribute("aria-current", "date");
            }
            if (dateValue === input.value) {
                day.classList.add("is-selected");
            }

            day.addEventListener("click", () => {
                input.value = dateValue;
                input.dispatchEvent(new Event("input", { bubbles: true }));
                input.dispatchEvent(new Event("change", { bubbles: true }));
                closeCalendar(true);
            });

            day.addEventListener("keydown", (event) => {
                let nextDate = new Date(date);
                if (event.key === "ArrowLeft") {
                    nextDate.setDate(nextDate.getDate() - 1);
                } else if (event.key === "ArrowRight") {
                    nextDate.setDate(nextDate.getDate() + 1);
                } else if (event.key === "ArrowUp") {
                    nextDate.setDate(nextDate.getDate() - 7);
                } else if (event.key === "ArrowDown") {
                    nextDate.setDate(nextDate.getDate() + 7);
                } else if (event.key === "Home") {
                    nextDate.setDate(nextDate.getDate() - nextDate.getDay());
                } else if (event.key === "End") {
                    nextDate.setDate(nextDate.getDate() + (6 - nextDate.getDay()));
                } else if (event.key === "PageUp" || event.key === "PageDown") {
                    const monthOffset = event.key === "PageUp" ? -1 : 1;
                    const dayOfMonth = nextDate.getDate();
                    nextDate = new Date(
                        nextDate.getFullYear(),
                        nextDate.getMonth() + monthOffset,
                        Math.min(
                            dayOfMonth,
                            new Date(
                                nextDate.getFullYear(),
                                nextDate.getMonth() + monthOffset + 1,
                                0
                            ).getDate()
                        )
                    );
                } else {
                    return;
                }

                if (!isAllowedDate(nextDate)) {
                    return;
                }

                event.preventDefault();
                displayedMonth = new Date(
                    nextDate.getFullYear(),
                    nextDate.getMonth(),
                    1
                );
                renderCalendar(nextDate);
                popover.querySelector(`[data-calendar-date="${formatCalendarDate(nextDate)}"]`)?.focus();
            });

            days.append(day);
        }

        popover.replaceChildren(header, weekdays, days);
        popover.setAttribute("aria-labelledby", titleId);

        previous.addEventListener("click", () => {
            displayedMonth = new Date(
                displayedMonth.getFullYear(),
                displayedMonth.getMonth() - 1,
                1
            );
            renderCalendar(focusDate);
            popover.querySelector("[data-calendar-previous]")?.focus();
        });
        next.addEventListener("click", () => {
            displayedMonth = new Date(
                displayedMonth.getFullYear(),
                displayedMonth.getMonth() + 1,
                1
            );
            renderCalendar(focusDate);
            popover.querySelector("[data-calendar-next]")?.focus();
        });

        if (focusDate) {
            popover.querySelector(`[data-calendar-date="${formatCalendarDate(focusDate)}"]`)?.focus();
        }
    }

    function closeCalendar(returnFocus) {
        if (popover.hidden) {
            return;
        }

        toggle.setAttribute("aria-expanded", "false");
        wrapper.classList.remove("is-open");
        wrapper.classList.add("is-closing");
        popover.inert = true;
        popover.addEventListener("animationend", () => {
            if (wrapper.classList.contains("is-closing")) {
                popover.hidden = true;
                wrapper.classList.remove("is-closing");
            }
        }, { once: true });

        if (returnFocus) {
            toggle.focus();
        }
    }

    function openCalendar() {
        document.querySelectorAll(".calendar-field.is-open").forEach((openWrapper) => {
            if (openWrapper !== wrapper) {
                openWrapper.classList.remove("is-open");
                openWrapper.querySelector(".calendar-toggle").setAttribute("aria-expanded", "false");
                openWrapper.querySelector(".calendar-popover").hidden = true;
                openWrapper.querySelector(".calendar-popover").inert = true;
            }
        });

        const selectedDate = parseCalendarDate(input.value) || new Date();
        displayedMonth = new Date(selectedDate.getFullYear(), selectedDate.getMonth(), 1);
        popover.hidden = false;
        popover.inert = false;
        wrapper.classList.remove("is-closing");
        wrapper.classList.add("is-open");
        toggle.setAttribute("aria-expanded", "true");
        renderCalendar(selectedDate);

        const selectedDay = popover.querySelector(
            `[data-calendar-date="${formatCalendarDate(selectedDate)}"]:not(:disabled)`
        );
        const firstAvailableDay = popover.querySelector(".calendar-day:not(:disabled)");
        (selectedDay || firstAvailableDay)?.focus();
    }

    toggle.addEventListener("click", () => {
        if (toggle.getAttribute("aria-expanded") === "true") {
            closeCalendar(false);
        } else {
            openCalendar();
        }
    });

    popover.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
            event.preventDefault();
            closeCalendar(true);
        }
    });

    input.addEventListener("change", () => {
        if (!popover.hidden) {
            const changedDate = parseCalendarDate(input.value);
            if (changedDate) {
                displayedMonth = new Date(
                    changedDate.getFullYear(),
                    changedDate.getMonth(),
                    1
                );
                renderCalendar(changedDate);
            }
        }
    });
});

document.addEventListener("pointerdown", (event) => {
    document.querySelectorAll(".calendar-field.is-open").forEach((wrapper) => {
        if (!wrapper.contains(event.target)) {
            wrapper.classList.remove("is-open");
            wrapper.querySelector(".calendar-toggle").setAttribute("aria-expanded", "false");
            wrapper.querySelector(".calendar-popover").hidden = true;
            wrapper.querySelector(".calendar-popover").inert = true;
        }
    });
});

document.querySelectorAll("select").forEach((select, index) => {
    if (select.multiple || select.size > 1 || select.dataset.animatedSelect === "true") {
        return;
    }

    select.dataset.animatedSelect = "true";
    const wrapper = document.createElement("div");
    wrapper.className = "animated-select";
    const trigger = document.createElement("button");
    const list = document.createElement("div");
    const label = select.labels && select.labels.length
        ? select.labels[0].textContent.trim()
        : select.name
            .replace(/[_-]+/g, " ")
            .replace(/\b\w/g, (letter) => letter.toUpperCase()) || "Select an option";
    const listId = `animated-select-list-${index + 1}`;

    trigger.type = "button";
    trigger.className = `animated-select-trigger ${select.className}`.trim();
    trigger.id = select.id ? `${select.id}-trigger` : `animated-select-trigger-${index + 1}`;
    trigger.setAttribute("role", "combobox");
    trigger.setAttribute("aria-haspopup", "listbox");
    trigger.setAttribute("aria-expanded", "false");
    trigger.setAttribute("aria-controls", listId);
    trigger.setAttribute("aria-label", select.getAttribute("aria-label") || label);
    trigger.setAttribute("aria-required", String(select.required));
    trigger.disabled = select.disabled;

    if (select.required) {
        select.dataset.animatedRequired = "true";
        select.required = false;
    }
    select.classList.add("animated-select-native");
    select.tabIndex = -1;
    select.setAttribute("aria-hidden", "true");

    select.parentNode.insertBefore(wrapper, select);
    wrapper.append(select, trigger, list);

    const error = document.createElement("span");
    error.className = "animated-select-error";
    error.setAttribute("role", "alert");
    error.hidden = true;
    error.textContent = `${label} is required.`;
    wrapper.append(error);

    if (select.id) {
        document.querySelectorAll("label[for]").forEach((associatedLabel) => {
            if (associatedLabel.htmlFor === select.id) {
                associatedLabel.htmlFor = trigger.id;
            }
        });
    }

    list.id = listId;
    list.className = "animated-select-list";
    list.setAttribute("role", "listbox");
    list.setAttribute("aria-labelledby", trigger.id);
    list.hidden = true;

    const options = Array.from(select.options).map((option, optionIndex) => {
        const item = document.createElement("button");
        item.type = "button";
        item.className = "animated-select-option";
        item.setAttribute("role", "option");
        item.setAttribute("aria-selected", String(option.selected));
        item.disabled = option.disabled;
        item.tabIndex = -1;
        item.dataset.optionIndex = String(optionIndex);

        const text = document.createElement("span");
        text.textContent = option.textContent.trim();
        const check = document.createElement("span");
        check.className = "animated-select-check";
        check.setAttribute("aria-hidden", "true");
        check.textContent = "✓";
        item.append(text, check);
        list.append(item);

        item.addEventListener("click", () => {
            select.selectedIndex = optionIndex;
            select.dispatchEvent(new Event("input", { bubbles: true }));
            select.dispatchEvent(new Event("change", { bubbles: true }));
            updateSelection();
            closeSelect(true);
        });
        return item;
    });

    function updateSelection() {
        const selectedOption = select.options[select.selectedIndex];
        trigger.firstChild
            ? trigger.firstChild.textContent = selectedOption ? selectedOption.textContent.trim() : ""
            : trigger.append(document.createTextNode(selectedOption ? selectedOption.textContent.trim() : ""));
        options.forEach((option, optionIndex) => {
            option.setAttribute("aria-selected", String(optionIndex === select.selectedIndex));
        });
        trigger.removeAttribute("aria-invalid");
        error.hidden = true;
        wrapper.classList.remove("has-error");
    }

    function closeSelect(returnFocus) {
        trigger.setAttribute("aria-expanded", "false");
        wrapper.classList.remove("is-open");
        wrapper.classList.add("is-closing");
        list.inert = true;
        list.addEventListener("animationend", () => {
            if (wrapper.classList.contains("is-closing")) {
                list.hidden = true;
                wrapper.classList.remove("is-closing");
            }
        }, { once: true });
        if (returnFocus) {
            trigger.focus();
        }
    }

    function openSelect(focusLast) {
        document.querySelectorAll(".animated-select.is-open").forEach((openWrapper) => {
            if (openWrapper !== wrapper) {
                openWrapper.querySelector(".animated-select-trigger").setAttribute("aria-expanded", "false");
                openWrapper.querySelector(".animated-select-list").hidden = true;
                openWrapper.querySelector(".animated-select-list").inert = true;
                openWrapper.classList.remove("is-open");
                openWrapper.classList.remove("is-closing");
            }
        });
        list.hidden = false;
        list.inert = false;
        wrapper.classList.remove("is-closing");
        wrapper.classList.add("is-open");
        trigger.setAttribute("aria-expanded", "true");
        const enabledOptions = options.filter((option) => !option.disabled);
        const activeOption = focusLast
            ? enabledOptions[enabledOptions.length - 1]
            : enabledOptions.find((option) => Number(option.dataset.optionIndex) === select.selectedIndex)
                || enabledOptions[0];
        if (activeOption) {
            activeOption.focus();
        }
    }

    trigger.addEventListener("click", () => {
        if (trigger.getAttribute("aria-expanded") === "true") {
            closeSelect(false);
        } else {
            openSelect(false);
        }
    });

    trigger.addEventListener("keydown", (event) => {
        if (["ArrowDown", "ArrowUp", "Enter", " "].includes(event.key)) {
            event.preventDefault();
            openSelect(event.key === "ArrowUp");
        }
    });

    list.addEventListener("keydown", (event) => {
        const currentOption = event.target.closest(".animated-select-option");
        if (!currentOption) {
            return;
        }

        const enabledOptions = options.filter((option) => !option.disabled);
        const currentIndex = enabledOptions.indexOf(currentOption);
        let nextIndex = currentIndex;

        if (event.key === "ArrowDown") {
            nextIndex = (currentIndex + 1) % enabledOptions.length;
        } else if (event.key === "ArrowUp") {
            nextIndex = (currentIndex - 1 + enabledOptions.length) % enabledOptions.length;
        } else if (event.key === "Home") {
            nextIndex = 0;
        } else if (event.key === "End") {
            nextIndex = enabledOptions.length - 1;
        } else if (event.key === "Escape") {
            event.preventDefault();
            closeSelect(true);
            return;
        } else if (event.key === "Tab") {
            closeSelect(false);
            return;
        } else {
            return;
        }

        event.preventDefault();
        enabledOptions[nextIndex].focus();
    });

    select.addEventListener("change", updateSelection);
    updateSelection();
});

document.addEventListener("pointerdown", (event) => {
    document.querySelectorAll(".animated-select.is-open").forEach((wrapper) => {
        if (!wrapper.contains(event.target)) {
            wrapper.classList.remove("is-open");
            wrapper.querySelector(".animated-select-trigger").setAttribute("aria-expanded", "false");
            wrapper.querySelector(".animated-select-list").hidden = true;
            wrapper.querySelector(".animated-select-list").inert = true;
        }
    });
});

document.querySelectorAll('input[type="file"][name="item_image"]').forEach((input) => {
    const form = input.closest("form");
    const preview = form?.querySelector("[data-image-preview]");
    const previewImage = preview?.querySelector("[data-image-preview-image]");
    const imageName = preview?.querySelector("[data-image-name]");
    const removeButton = preview?.querySelector("[data-image-remove]");
    const status = form?.querySelector("[data-image-status]");
    let previewUrl = null;

    if (!form || !preview || !previewImage || !imageName || !removeButton || !status) {
        return;
    }

    const clearSelection = () => {
        input.value = "";
        previewImage.removeAttribute("src");
        imageName.textContent = "";
        preview.hidden = true;
        status.hidden = true;
        status.textContent = "";
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
            previewUrl = null;
        }
    };

    input.addEventListener("change", () => {
        const file = input.files?.[0];
        if (!file) {
            clearSelection();
            return;
        }

        const allowedTypes = ["image/jpeg", "image/png", "image/webp"];
        const extension = file.name.split(".").pop()?.toLowerCase();
        const allowedExtensions = ["jpg", "jpeg", "png", "webp"];
        if (
            !allowedTypes.includes(file.type) &&
            !(file.type === "" && allowedExtensions.includes(extension))
        ) {
            clearSelection();
            status.textContent = "Choose a JPG, JPEG, PNG or WEBP image.";
            status.hidden = false;
            return;
        }

        if (file.size > 2 * 1024 * 1024) {
            clearSelection();
            status.textContent = "Image size must be 2 MB or less.";
            status.hidden = false;
            return;
        }

        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        previewUrl = URL.createObjectURL(file);
        previewImage.src = previewUrl;
        imageName.textContent = file.name;
        preview.hidden = false;
        status.hidden = true;
        status.textContent = "";
    });

    removeButton.addEventListener("click", clearSelection);
});

document.querySelectorAll("img[data-loading-skeleton]").forEach((image) => {
    const finishImageLoad = () => {
        image.classList.remove("image-skeleton");
        image.removeAttribute("data-loading-skeleton");
    };

    image.addEventListener("load", finishImageLoad, { once: true });
    image.addEventListener(
        "error",
        () => {
            const fallback = document.createElement("div");
            fallback.className = "no-image image-load-error";
            fallback.setAttribute("role", "img");
            fallback.setAttribute("aria-label", image.alt || "Image unavailable");
            fallback.textContent = "Image unavailable";
            image.replaceWith(fallback);
        },
        { once: true }
    );

    if (image.complete) {
        if (image.naturalWidth > 0) {
            finishImageLoad();
        } else {
            image.dispatchEvent(new Event("error"));
        }
    }
});

document.querySelectorAll("form").forEach((form) => {
    form.addEventListener("submit", (event) => {
        const missingSelect = Array.from(
            form.querySelectorAll("select[data-animated-required='true']")
        ).find((select) => select.value === "");

        if (missingSelect) {
            event.preventDefault();
            const wrapper = missingSelect.closest(".animated-select");
            const trigger = wrapper.querySelector(".animated-select-trigger");
            trigger.setAttribute("aria-invalid", "true");
            wrapper.classList.add("has-error");
            wrapper.querySelector(".animated-select-error").hidden = false;
            trigger.focus();
            return;
        }

        if (form.dataset.submitting === "true") {
            event.preventDefault();
            return;
        }

        form.dataset.submitting = "true";
        form.setAttribute("aria-busy", "true");
        form.classList.add("is-submitting");

        if (event.submitter instanceof HTMLButtonElement) {
            event.submitter.classList.add("is-loading");
        }

        const status = document.createElement("span");
        status.className = "visually-hidden submit-status";
        status.setAttribute("role", "status");
        status.textContent =
            form.method.toLowerCase() === "get"
                ? "Loading results…"
                : "Submitting…";
        form.append(status);
    });
});

window.addEventListener("pageshow", () => {
    document.querySelectorAll("form[aria-busy='true']").forEach((form) => {
        form.removeAttribute("aria-busy");
        form.classList.remove("is-submitting");
        delete form.dataset.submitting;
        form.querySelectorAll(".is-loading").forEach((button) => {
            button.classList.remove("is-loading");
        });
        form.querySelectorAll(".submit-status").forEach((status) => {
            status.remove();
        });
    });
});

if (!reducedMotion && "IntersectionObserver" in window) {
    const revealTargets = document.querySelectorAll(
        ".hero, .search-section, .items-section, .item-card, " +
        ".student-dashboard, .search-page, .register-container, " +
        ".form-container, .details-container, .reports-container, " +
        ".profile-container, .profile-page-heading, .profile-card, " +
        ".dashboard-card, .report-card, " +
        ".search-results .item-card"
    );

    revealTargets.forEach((element, index) => {
        element.dataset.reveal = "";
        if (element.matches(".item-card, .dashboard-card, .report-card")) {
            element.style.setProperty(
                "--reveal-delay",
                `${Math.min(index % 4, 3) * 80}ms`
            );
        }
    });

    document.documentElement.classList.add("motion-ready");

    const revealObserver = new IntersectionObserver(
        (entries, observer) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add("is-visible");
                    observer.unobserve(entry.target);
                }
            });
        },
        { threshold: 0.12, rootMargin: "0px 0px -36px 0px" }
    );

    revealTargets.forEach((element) => revealObserver.observe(element));
} else {
    document
        .querySelectorAll(".hero, .search-section, .items-section, .item-card")
        .forEach((element) => element.classList.add("is-visible"));
}