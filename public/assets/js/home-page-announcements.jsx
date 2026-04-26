// Arxeio: public\assets\js\home-page-announcements.jsx
// Rolos: Xeirizetai dynamic kommatia tis arxikis selidas kai fernei dedomena apo backend services.
// Simeiosi: Allages edo epireazoun ti symperifora sto browser kai ta API requests pou stelnei to UI.
// React component pou fortonei kai deixnei tis teleftaies anakoinoseis stin arxiki.
function Announcements() {
    // Kratame ksexorista loading/error gia na deixnoume kathari katastasi sto UI.
    const [announcements, setAnnouncements] = React.useState([]);
    const [loading, setLoading] = React.useState(true);
    const [error, setError] = React.useState(false);

    // Kanei normalize tin imerominia apo MySQL string se morfi pou vlepoun oi xristes.
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
        // Fortonei tis anakoinoseis otan anoigei i arxiki selida.
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

    // Ta parakato early returns kratane to render aplo gia loading/error/empty states.
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
                <a
                    key={item.announcement_id || index}
                    href={`/parents-council-platform-group5/public/announcements.php?open=${encodeURIComponent(item.announcement_id)}`}
                    className="home-feed-link"
                    aria-label={`Άνοιγμα ανακοίνωσης: ${item.announcement_title || 'Ανακοίνωση'}`}
                >
                    <article className="home-feed-item announcement-item">
                        <div className="home-feed-meta">
                            <span className="home-feed-badge">Ανακοίνωση</span>
                            <span className="home-feed-date">{formatDate(item.announcement_date)}</span>
                        </div>
                        <p className="announcement-title home-feed-title">{item.announcement_title}</p>
                        <p className="announcement-description home-feed-description">{item.announcement_description}</p>
                    </article>
                </a>
            ))}
        </div>
    );
}

ReactDOM.createRoot(document.getElementById("announcements-root")).render(<Announcements />);
