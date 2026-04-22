function UpcomingEvents() {
    const [events, setEvents] = React.useState([]);
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState(false);

    const formatDate = (value) => {
        if (!value) return "Εκδήλωση";
        const date = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(date.getTime())) return "Εκδήλωση";
        return date.toLocaleDateString("el-GR", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });
    };

    React.useEffect(() => {
        fetch('/parents-council-platform-group5/app/services/EventsService.php')
        .then(result => result.json())
        .then(data => {
            setEvents(Array.isArray(data) ? data : []);
            setLoading(false);
        })
        .catch(error => {
            console.error('Error fetching events:', error);
            setError(true);
            setLoading(false);
        });
    }, []);

    if (loading) {
        return <p className="home-list-state">Φόρτωση εκδηλώσεων...</p>;
    }

    if (error) {
        return <p className="home-list-state">Δεν ήταν δυνατή η φόρτωση των εκδηλώσεων.</p>;
    }

    if (events.length === 0) {
        return <p className="home-list-state">Δεν υπάρχουν διαθέσιμες εκδηλώσεις αυτή τη στιγμή.</p>;
    }

    return (
        <div className="home-list events-header">
            {events.map((item, index) => (
                <a
                    key={item.event_id || index}
                    href={`/parents-council-platform-group5/public/events.php?open=${encodeURIComponent(item.event_id)}`}
                    className="home-feed-link"
                    aria-label={`Άνοιγμα εκδήλωσης: ${item.event_title || 'Εκδήλωση'}`}
                >
                    <article className="home-feed-item event-item">
                        <div className="home-feed-meta">
                            <span className="home-feed-badge event-badge">Εκδήλωση</span>
                            <span className="home-feed-date">{formatDate(item.event_date)}</span>
                        </div>
                        <p className="event-title home-feed-title">{item.event_title}</p>
                        <p className="event-description home-feed-description">{item.event_description}</p>
                    </article>
                </a>
            ))}
        </div>
    );
}

ReactDOM.createRoot(document.getElementById("upcoming-events-root")).render(<UpcomingEvents />);
