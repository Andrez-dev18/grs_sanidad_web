document.addEventListener("DOMContentLoaded", function () {
  

  const calendarGrid = document.getElementById("calendarGrid");
  const currentMonthDisplay = document.getElementById("currentMonthDisplay");
  const planModal = document.getElementById("planModal");

  let currentDate = new Date();
  let events = [];

  const monthNames = [
    "Enero",
    "Febrero",
    "Marzo",
    "Abril",
    "Mayo",
    "Junio",
    "Julio",
    "Agosto",
    "Septiembre",
    "Octubre",
    "Noviembre",
    "Diciembre",
  ];

  function initCalendar() {
    renderCalendar();
  }

  function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    if (currentMonthDisplay)
      currentMonthDisplay.textContent = `${monthNames[month]} ${year}`;


    const firstDay = new Date(year, month, 1);

    const lastDay = new Date(year, month + 1, 0);


    const startDayIndex = firstDay.getDay();
    const totalDays = lastDay.getDate();

    if (calendarGrid) {
      calendarGrid.innerHTML = "";


      for (let i = 0; i < startDayIndex; i++) {
        const cell = document.createElement("div");
        cell.className = "calendar-day empty";
        calendarGrid.appendChild(cell);
      }


      for (let i = 1; i <= totalDays; i++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(
          i
        ).padStart(2, "0")}`;
        const cell = document.createElement("div");
        cell.className = "calendar-day";


        const today = new Date();
        if (
          i === today.getDate() &&
          month === today.getMonth() &&
          year === today.getFullYear()
        ) {
          cell.classList.add("today");
        }


        const dayNum = document.createElement("div");
        dayNum.className = "day-number";
        dayNum.textContent = i;
        cell.appendChild(dayNum);


        const dayEvents = events.filter((e) => e.date === dateStr);
        dayEvents.forEach((event) => {
          const eventEl = document.createElement("div");
          eventEl.className = "day-event";
          eventEl.textContent = `${event.quantity}x ${formatEventType(
            event.type
          )}`;
          eventEl.style.backgroundColor = getEventColor(event.type);
          cell.appendChild(eventEl);
        });


        cell.onclick = () => openPlanModal(dateStr);

        calendarGrid.appendChild(cell);
      }
    }
  }

  function prevMonth() {
    currentDate.setMonth(currentDate.getMonth() - 1);
    renderCalendar();
  }

  function nextMonth() {
    currentDate.setMonth(currentDate.getMonth() + 1);
    renderCalendar();
  }

  function openPlanModal(dateStr) {
    if (dateStr) {
      document.getElementById("planFecha").value = dateStr;
    } else {

      const today = new Date().toISOString().split("T")[0];
      document.getElementById("planFecha").value = today;
    }
    planModal.classList.add("active");
  }

  function closePlanModal() {
    planModal.classList.remove("active");
    document.getElementById("planForm").reset();
  }

  function handlePlanSubmit(e) {
    e.preventDefault();
    const date = document.getElementById("planFecha").value;
    const type = document.getElementById("planTipo").value;
    const quantity = document.getElementById("planCantidad").value;
    const notes = document.getElementById("planNotas").value;

    events.push({ date, type, quantity, notes });

    renderCalendar();
    closePlanModal();
    return false;
  }

  function formatEventType(type) {
    const types = {
      aves_vivas: "Aves Vivas",
      sueros: "Sueros",
      organos_refrigerados: "Órganos Ref.",
      organos_formol: "Órganos Formol",
      culturete: "Culturete",
      agua: "Agua",
      aves_muertas: "Aves Muertas",
    };
    return types[type] || type;
  }

  function getEventColor(type) {
    const colors = {
      aves_vivas: "#667eea",
      sueros: "#ed8936",
      organos_refrigerados: "#48bb78",
      organos_formol: "#a0aec0",
      culturete: "#f56565",
      agua: "#4299e1",
      aves_muertas: "#2d3748",
    };
    return colors[type] || "#cbd5e0";
  }

});

