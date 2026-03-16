function UpcomingEvents() {
    const [events, setEvents] = React.useState([]);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        fetch('/parents-council-platform-group5/app/services/EventsService.php')
        .then(result =>result.json())
        .then(data => {
            setEvents(data);
            setLoading(false);
        })
        .catch(error => console.error('Error fetching events:', error));
    }, []);

    return (
        <div>
            <div className="events-header">{
                loading ? (
                    <p>Loading...</p>

                ) : (
                    events.map((item, index) => (
                        <div key={index} className="event-item">
                            <p className="event-title">{item.event_title}</p>
                            <p className="event-description">{item.event_description}</p>
                        </div>
                    ))
                )
            }
            </div>
        </div>
    );
}

ReactDOM.createRoot(document.getElementById("upcoming-events-root")).render(<UpcomingEvents />);