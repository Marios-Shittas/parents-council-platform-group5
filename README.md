# Parents Council Platform

Πλατφόρμα διαχείρισης Συλλόγου Γονέων & Κηδεμόνων Γυμνασίου Αγίου Αθανασίου

## Απαιτήσεις

- PHP 7.4+
- MySQL 5.7+
- Apache Web Server
- XAMPP (για local development)

## Εγκατάσταση

### 1. Κλωνοποίηση του Project
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/
# (Το project είναι ήδη εδώ)
```

### 2. Βάση Δεδομένων
```bash
# Άνοιξε το phpMyAdmin: http://localhost/phpmyadmin
# Δημιούργησε βάση με όνομα: parents_council
# Import το αρχείο: database/parents_council.sql
```

### 3. **ΣΗΜΑΝΤΙΚΟ: Δικαιώματα Φακέλων** ⚠️

Για να λειτουργήσει το upload αρχείων (εικόνες, έγγραφα), τρέξε:

```bash
cd /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5
./fix_permissions.sh
```

Ή χειροκίνητα:
```bash
chmod 777 public/assets/Announcements_img
chmod 777 public/assets/Events_img
chmod 777 public/assets/Applications_docs
chmod 777 public/assets/Submissions_docs
chmod 777 public/assets/Products_img
chmod 777 public/assets/Posts_img
```

### 4. Ρύθμιση Database Connection
Έλεγξε το αρχείο: `app/config/db.php`
```php
$host = "localhost";
$user = "root";
$password = "";
$database = "parents_council";
```

### 5. Πρόσβαση στην Εφαρμογή

**Public Site:**
- http://localhost/parents-council-platform-group5/public/

**Admin Panel:**
- http://localhost/parents-council-platform-group5/public/admin/

## Δομή Project

```
parents-council-platform-group5/
├── app/
│   ├── config/              # Database configuration
│   ├── includes/            # Shared PHP includes (header, footer, auth)
│   └── services/            # Business logic services
├── database/                # SQL files (schema, seed data)
├── public/
│   ├── admin/              # Admin panel pages
│   ├── assets/             # CSS, JS, Images, Uploads
│   └── *.php              # Public pages
├── fix_permissions.sh      # Script για fix upload permissions
├── PERMISSIONS_FIX.md      # Οδηγίες για permissions
└── FIX_UPLOAD_ISSUE.md    # Οδηγίες για upload debugging
```

## Features

### Public Pages
- 🏠 Home - Αρχική σελίδα
- 📢 Announcements - Ανακοινώσεις
- 📅 Events - Εκδηλώσεις
- 📝 Applications - Αιτήσεις
- 🛍️ Eshop - Κατάστημα

### Admin Panel
- 📋 Dashboard
- ✏️ Διαχείριση Ανακοινώσεων
- 📆 Διαχείριση Εκδηλώσεων
- 👥 Διαχείριση Χρηστών

## Troubleshooting

### Πρόβλημα: Τα αρχεία δεν ανεβαίνουν

**Λύση:** Πρόβλημα permissions. Διάβασε το [PERMISSIONS_FIX.md](PERMISSIONS_FIX.md)

```bash
./fix_permissions.sh
```

### Πρόβλημα: "Connection failed" error

**Λύση:** Έλεγξε ότι:
1. Το MySQL τρέχει (XAMPP Control Panel)
2. Η βάση `parents_council` υπάρχει
3. Τα credentials στο `app/config/db.php` είναι σωστά

### Πρόβλημα: Blank page ή PHP errors

**Λύση:** Έλεγξε τα error logs:
```bash
tail -f /Applications/XAMPP/xamppfiles/logs/error_log
```

### Debug Upload Issues

Χρησιμοποίησε το diagnostic tool:
http://localhost/parents-council-platform-group5/public/admin/debug_upload.php

## Ανάπτυξη

### File Upload Guidelines

Όλα τα uploads γίνονται μέσω services:
- `AnnouncementsService.php` - Εικόνες ανακοινώσεων
- `EventsService.php` - Εικόνες εκδηλώσεων
- `ApplicationsService.php` - Έγγραφα αιτήσεων

**Supported formats:**
- Εικόνες: JPG, JPEG, PNG, GIF (max 5MB)
- Έγγραφα: PDF, DOC, DOCX (max 10MB)

### Database Schema

Δες το `database/schema.sql` για τη δομή της βάσης.

Κύριοι πίνακες:
- `Users` - Χρήστες συστήματος
- `Announcements` + `AnnouncementsImages` - Ανακοινώσεις
- `Events` + `EventsImages` - Εκδηλώσεις
- `Applications` - Αιτήσεις
- `Payments` - Πληρωμές

## Security Notes

### Για Local Development
- ✅ Δικαιώματα 777 είναι OK
- ✅ Password κενό για MySQL root είναι OK

### Για Production Server
- ⚠️ ΜΗΝ χρησιμοποιήσεις 777 permissions!
- ⚠️ Άλλαξε το MySQL password
- ⚠️ Ενεργοποίησε HTTPS
- ⚠️ Προσθήκη CSRF protection (υπάρχει το `csrf.php`)

## Resources

- [PHP Documentation](https://www.php.net/docs.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [Bootstrap 4.6](https://getbootstrap.com/docs/4.6/)

## License

Educational project για το Γυμνάσιο Αγίου Αθανασίου.

## Support

Για προβλήματα και ερωτήσεις:
1. Έλεγξε το [PERMISSIONS_FIX.md](PERMISSIONS_FIX.md)
2. Έλεγξε το [FIX_UPLOAD_ISSUE.md](FIX_UPLOAD_ISSUE.md)
3. Χρησιμοποίησε το debug tool
4. Έλεγξε τα PHP error logs
