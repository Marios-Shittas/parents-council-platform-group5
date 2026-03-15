function Announcements() {
    const [announcements, setAnnouncements] = React.useState([]);
    const [loading, setLoading] = React.useState(true);

    React.useEffect(() => {
        fetch('/parents-council-platform-group5/app/services/AnnouncementsService.php')
        .then(result =>result.json())
        .then(data => {
            setAnnouncements(data);
            setLoading(false);
        })
        .catch(error => console.error('Error fetching announcements:', error));
    }, []);

    return (
        <div>
            <div className="announcements-header">{
                loading ? (
                    <p>Loading...</p>

                ) : (
                    announcements.map((item, index) => (
                        <div key={index} className="announcement-item">
                            <p className="announcement-title">{item.announcement_title}</p>
                            <p className="announcement-description">{item.announcement_description}</p>
                        </div>
                    ))
                )
            }
            </div>
        </div>
    );
}

ReactDOM.createRoot(document.getElementById("announcements-root")).render(<Announcements />);