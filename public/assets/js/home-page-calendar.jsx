// Arxeio: public\assets\js\home-page-calendar.jsx
// Rolos: Xeirizetai dynamic kommatia tis arxikis selidas kai fernei dedomena apo backend services.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
// React calendar gia tin arxiki: deixnei ekdiloseis, anakoinoseis kai argies ana imera.
// Ta labels einai stathera arrays gia na min ta ksanaypologizoume se kathe render.
const MONTHS = [
  "Ιανουάριος",
  "Φεβρουάριος",
  "Μάρτιος",
  "Απρίλιος",
  "Μάιος",
  "Ιούνιος",
  "Ιούλιος",
  "Αύγουστος",
  "Σεπτέμβριος",
  "Οκτώβριος",
  "Νοέμβριος",
  "Δεκέμβριος"
];

const WEEKDAYS = ["ΔΕΥ", "ΤΡΙ", "ΤΕΤ", "ΠΕΜ", "ΠΑΡ", "ΣΑΒ", "ΚΥΡ"];
const TYPE_LABELS = {
  holiday: "ΑΡΓΙΑ",
  event: "ΕΚΔΗΛΩΣΗ",
  announcement: "ΑΝΑΚΟΙΝΩΣΗ"
};

function getTypeLabel(item) {
  // Kathe item exei type apo backend, alliws peftei se geniko label.
  return TYPE_LABELS[item?.type] || "ΚΑΤΑΧΩΡΙΣΗ";
}

function formatItemTitle(item) {
  const label = getTypeLabel(item);
  const title = String(item?.title || "").trim();
  return `${label}: ${title}`;
}

class Calendar extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      m: new Date().getMonth(),
      y: new Date().getFullYear(),
      events: [],
      selectedDay: null
    };
  }

  componentDidMount() {
    // Fortonei to enopoihmeno calendar feed apo PHP service.
    fetch(window.appServiceUrl('CalendarEventsService.php'))
      .then(result => result.json())
      .then(data => this.setState({ events: data }))
      .catch(error => console.error('Error fetching events:', error));
  }

  chM(d) {
    let m = this.state.m + d;
    let y = this.state.y;
    if (m < 0) { m = 11; y -= 1; }
    else if (m > 11) { m = 0; y += 1; }
    this.setState({ m, y, selectedDay: null });
  }

  render() {
    const { m, y, events, selectedDay } = this.state;

    // Ypologizoume ta kena cells prin tin 1i tou mina gia na stithei sosta to grid.
    const firstDay = new Date(y, m, 1).getDay();
    const daysInMonth = new Date(y, m + 1, 0).getDate();
    const startOffset = firstDay === 0 ? 6 : firstDay - 1;
    const cells = [];
    for (let i = 0; i < startOffset; i++) cells.push(null);
    for (let d = 1; d <= daysInMonth; d++) cells.push(d);

    const today = new Date();

    // Filtrarei ta items pou tairiazoun me tin imera pou patise o xristis.
    const selectedEvents = selectedDay ? events.filter(event => {
      const eventDate = new Date(event.date);
      return eventDate.getDate() === selectedDay &&
             eventDate.getMonth() === m &&
             eventDate.getFullYear() === y;
    }) : [];

    return (
      <div>
        <div className="cal-header">
          <span>
            <span className="cal-month">{MONTHS[m]}</span>
            <span> </span>
            <span className="cal-year">{y}</span>
          </span>
          <div className="nav-group">
            <button className="nav" onClick={() => this.chM(-1)}>&lt;</button>
            <button className="nav" onClick={() => this.chM(1)}>&gt;</button>
          </div>
        </div>

        <div className="cal-row">
          {WEEKDAYS.map(day => (
            <div key={day} className="cal-cell">{day}</div>
          ))}
        </div>

        <div className="cal-row">
          {cells.map((d, i) => {
            const dayEvents = d ? events.filter(event => {
              const eventDate = new Date(event.date);
              return eventDate.getDate() === d &&
                     eventDate.getMonth() === m &&
                     eventDate.getFullYear() === y;
            }) : [];

            return (
              <div
                key={i}
                className={`cal-cell ${
                  d && d === today.getDate() && m === today.getMonth() && y === today.getFullYear()
                    ? "current-day" : "normal-day"
                }`}
                onClick={() => d && dayEvents.length > 0 && this.setState({ selectedDay: selectedDay === d ? null : d })}
                style={{ cursor: dayEvents.length > 0 ? 'pointer' : 'default' }}
              >
                {d || ''}
                {dayEvents.length > 0 && (
                  <>
                    <div className={`event-title-preview ${
                      dayEvents[0].type === 'holiday'
                        ? 'holiday-preview'
                        : dayEvents[0].type === 'announcement'
                          ? 'announcement-preview'
                          : ''
                    }`}>
                      {formatItemTitle(dayEvents[0])}
                    </div>
                    {dayEvents.length > 1 && (
                      <div className="event-count-preview">
                        +{dayEvents.length - 1} ακόμη
                      </div>
                    )}
                  </>
                )}
              </div>
            );
          })}
        </div>

        {selectedDay && selectedEvents.length > 0 && (
          <div className="calendar-day-popup" role="dialog" aria-label="Event details">
            <button className="close-btn" type="button" onClick={() => this.setState({ selectedDay: null })}>✕</button>
            <div className="calendar-day-popup-content">
              {selectedEvents.map((event, i) => (
                <div key={i} className="calendar-day-popup-item">
                  <h5 className="event-detail-title">
                    <span className={`event-detail-type-label event-detail-type-label--${event.type || 'default'}`}>{getTypeLabel(event)}:</span>{' '}
                    <span className="event-detail-title-text">{event.title}</span>
                  </h5>
                  <p className="event-detail-description">{event.description}</p>
                  <p className="event-detail-date">{new Date(event.date).toLocaleDateString('el-GR')}</p>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    );
  }
}

ReactDOM.createRoot(document.getElementById("calendar-root")).render(<Calendar />);
