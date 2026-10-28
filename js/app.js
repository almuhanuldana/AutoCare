document.addEventListener("DOMContentLoaded", () => {
  const menuButton = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".main-nav");
  if (menuButton && nav) {
    menuButton.addEventListener("click", () => {
      const opened = nav.classList.toggle("open");
      menuButton.setAttribute("aria-expanded", String(opened));
    });
  }

  // Қызметтер бетіндегі браузерлік демо іздеу және сүзгі.
  const search = document.getElementById("serviceSearch");
  const filter = document.getElementById("serviceFilter");
  const grid = document.getElementById("serviceGrid");
  if (search && filter && grid) {
    const cards = [...grid.querySelectorAll(".catalog-card")];
    const count = document.getElementById("serviceCount");
    const empty = document.getElementById("emptyServices");
    const updateServices = () => {
      const query = search.value.trim().toLocaleLowerCase("kk");
      const category = filter.value;
      let visible = 0;
      cards.forEach(card => {
        const matchesName = card.dataset.name.toLocaleLowerCase("kk").includes(query);
        const matchesCategory = category === "all" || card.dataset.category === category;
        const show = matchesName && matchesCategory;
        card.hidden = !show;
        if (show) visible++;
      });
      count.textContent = `${visible} қызмет`;
      empty.hidden = visible !== 0;
    };
    search.addEventListener("input", updateServices);
    filter.addEventListener("change", updateServices);
  }

  // Қызметтер каталогынан жазылуға өткенде таңдалған қызметті алдын ала толтыру.
  const bookingService = document.getElementById("bookingService");
  if (bookingService) {
    const params = new URLSearchParams(window.location.search);
    const selectedService = params.get("service");
    if (selectedService) {
      [...bookingService.options].forEach(option => {
        if (option.text.trim() === selectedService) option.selected = true;
      });
    }
  }

  // Күн өрісінде өткен күнді таңдауға жол бермеу.
  const dateInput = document.getElementById("bookingDate");
  if (dateInput) {
    const today = new Date();
    const localToday = new Date(today.getTime() - today.getTimezoneOffset() * 60000)
      .toISOString().split("T")[0];
    dateInput.min = localToday;
  }

  const bookingForm = document.getElementById("bookingForm");
  if (bookingForm) {
    bookingForm.addEventListener("submit", event => {
      event.preventDefault();
      if (!bookingForm.reportValidity()) return;
      const data = new FormData(bookingForm);
      const message = document.getElementById("bookingMessage");
      message.textContent = `Рақмет, ${data.get("full_name")}! «${data.get("service")}» қызметіне өтінім формасы толтырылды. Бұл демо болғандықтан, мәліметтер базаға сақталған жоқ.`;
      message.hidden = false;
      message.scrollIntoView({behavior: "smooth", block: "nearest"});
      bookingForm.reset();
      if (dateInput) dateInput.min = new Date().toISOString().split("T")[0];
    });
  }

  // Кіру/тіркелу формаларының визуалды демо әрекеті.
  document.querySelectorAll("#demoForm").forEach(form => {
    form.addEventListener("submit", event => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const message = form.querySelector("#demoMessage");
      if (message) {
        message.textContent = "Форма дұрыс толтырылды. Нақты тіркелу/кіру PHP және MySQL қосылған келесі кезеңде іске асырылады.";
        message.hidden = false;
      }
    });
  });
});
