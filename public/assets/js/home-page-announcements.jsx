function Announcements() {
    const [announcements, setAnnouncements] = React.useState([]);
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState(false);

    const formatDate = (value) => {
        if (!value) return "Νέα ανακοίνωση";
        const date = new Date(String(value).replace(" ", "T"));
        if (Number.isNaN(date.getTime())) return "Νέα ανακοίνωση";
        return date.toLocaleDateString("el-GR", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });
    };

    React.useEffect(() => {
        fetch('/parents-council-platform-group5/app/services/AnnouncementsService.php')
        .then(result => result.json())
        .then(data => {
            setAnnouncements(Array.isArray(data) ? data : []);
            setLoading(false);
        })
        .catch(error => {
            console.error('Error fetching announcements:', error);
            setError(true);
            setLoading(false);
        });
    }, []);

    if (loading) {
        return <p className="home-list-state">Φόρτωση ανακοινώσεων...</p>;
    }

    if (error) {
        return <p className="home-list-state">Δεν ήταν δυνατή η φόρτωση των ανακοινώσεων.</p>;
    }

    if (announcements.length === 0) {
        return <p className="home-list-state">Δεν υπάρχουν διαθέσιμες ανακοινώσεις αυτή τη στιγμή.</p>;
    }

    return (
        <div className="home-list announcements-header">
            {announcements.map((item, index) => (
                <article key={index} className="home-feed-item announcement-item">
                    <div className="home-feed-meta">
                        <span className="home-feed-badge">Ανακοίνωση</span>
                        <span className="home-feed-date">{formatDate(item.announcement_date)}</span>
                    </div>
                    <p className="announcement-title home-feed-title">{item.announcement_title}</p>
                    <p className="announcement-description home-feed-description">{item.announcement_description}</p>
                </article>
            ))}
        </div>
    );
}

ReactDOM.createRoot(document.getElementById("announcements-root")).render(<Announcements />);
