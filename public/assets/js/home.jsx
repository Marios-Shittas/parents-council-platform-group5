const MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

function DayHeader() {
  return (
    <div className="cal-row">
      <div className="cal-cell">
          MON
      </div>
      <div className="cal-cell">
          TUE
      </div>
      <div className="cal-cell">
          WED
      </div>
      <div className="cal-cell">
          THU
      </div>
      <div className="cal-cell">
          FRI
      </div>
      <div className="cal-cell">
        SAT
      </div>
      <div className="cal-cell">
        SUN
      </div>
    </div>
  );
}

function Daygrid({cor, events, selectedDay, onDayClick}) {
  const firstDay = new Date(cor.y, cor.m, 1).getDay();
  const daysInMonth = new Date(cor.y, cor.m + 1, 0).getDate();
  const startOffset = (firstDay == 0 ? 6 : firstDay - 1);
  const cells = [];
  for (let i = 0; i < startOffset; i++) {
    cells.push(null);
  }
  for (let d = 1; d <= daysInMonth; d++) {
    cells.push(d);
  }

  const today = new Date();

  return (
    <div className="cal-row">
      {cells.map((d, i) => {
        const dayEvents = events.filter(event => {
          const eventDate = new Date(event.event_date);
          return eventDate.getDate() === d && eventDate.getMonth() === cor.m && eventDate.getFullYear() === cor.y;
        });

        return (
          <div 
            key={i}
            className={`cal-cell ${
              d && d == today.getDate() && cor.m == today.getMonth() && cor.y == today.getFullYear() ? "current-day" : "normal-day"
            } ${d === selectedDay ? "selected-day" : ""}`}
            onClick={dayEvents.length > 0 ? () => onDayClick(d) : null}
            style={dayEvents.length > 0 ? { cursor: 'pointer' } : {}}
          >
            {d || ''}
            {dayEvents.length > 0 && (
              <div className="event-titles">
                {dayEvents.map(event => (
                  <div key={event.event_id} className="event-title">{event.title}</div>
                ))}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

function Calendar() {
  const [cor, setCor] = React.useState({m: new Date().getMonth(), y: new Date().getFullYear()});
  const [events, setEvents] = React.useState([]);
  const [selectedDay, setSelectedDay] = React.useState(null);
  
  React.useEffect(() => {
    fetch('/parents-council-platform-group5/app/services/EventsService.php')
      .then(result => result.json())
      .then(data => {
        setEvents(data);
      })
      .catch(error => console.error('Error fetching events:', error));
  }, []);
  
  function chM(d) {
    let m = cor.m + d;
    let y = cor.y;

    if (m < 0){
      m = 11;
      y -= 1;
    }
    else if (m > 11) {
      m = 0;
      y += 1;
    }

    setCor({m, y});
    setSelectedDay(null); // Clear selection when changing month
  }

  function handleDayClick(d) {
    setSelectedDay(d);
  }

  return (
    <div>
      <div className="cal-header">
        <span>
          <span className="cal-month">
              {MONTHS[cor.m]}
          </span>
          <span> </span>
          <span className="cal-year">
            {cor.y}
          </span>
        </span>
        <div className="nav-group">
          <button className="nav" onClick={
            () => chM(-1)
            }
          >
            &lt;
          </button>
          <button className="nav" onClick={
            () => chM(1)
            }
          >
            &gt;
          </button>
        </div>
      </div>
      <DayHeader />
      <Daygrid cor={cor} events={events} selectedDay={selectedDay} onDayClick={handleDayClick} />
      {selectedDay && (
        <div className="selected-events">
          {events.filter(event => {
            const eventDate = new Date(event.event_date);
            return eventDate.getDate() === selectedDay && eventDate.getMonth() === cor.m && eventDate.getFullYear() === cor.y;
          }).map(event => (
            <div key={event.event_id}>Title: {event.title}</div>
          ))}
        </div>
      )}
    </div>
  );
}
ReactDOM.createRoot(document.getElementById("calendar-root")).render(<Calendar />);
