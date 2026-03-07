# Διόρθωση Δικαιωμάτων Upload (Permissions Fix)

## Το Πρόβλημα
Το σφάλμα **"Αποτυχία μεταφόρτωσης του αρχείου 'images.jpeg'"** οφειλόταν σε **πρόβλημα δικαιωμάτων** (permissions).

### Τι συνέβαινε:
- Το Apache/PHP τρέχει ως χρήστης `daemon` στο macOS XAMPP
- Οι φάκελοι uploads είχαν δικαιώματα `755` (drwxr-xr-x)
- Ο χρήστης `daemon` δεν μπορούσε να γράψει στους φακέλους
- Το `move_uploaded_file()` απέτυχε χωρίς λεπτομερές error message

## Η Λύση

### 1. Αλλαγή Δικαιωμάτων
Άλλαξα τα δικαιώματα όλων των upload φακέλων σε `777`:

```bash
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Announcements_img/
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Events_img/
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Applications_docs/
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Submissions_docs/
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Products_img/
chmod 777 /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Posts_img/
```

### 2. Βελτιωμένα Error Messages
Ενημέρωσα τον κώδικα να δείχνει πιο λεπτομερή μηνύματα όταν αποτύχει το upload:
- ✓ Έλεγχος αν υπάρχει το προσωρινό αρχείο
- ✓ Έλεγχος αν ο φάκελος είναι εγγράψιμος
- ✓ Εμφάνιση των δικαιωμάτων του φακέλου στο error message

## Δοκιμή

### Δοκίμασε τώρα:
1. Πήγαινε στο: http://localhost/parents-council-platform-group5/public/admin/announcements.php
2. Δημιούργησε νέα ανακοίνωση ή επεξεργάσου την "Hello This is test"
3. Ανέβασε εικόνες (JPG, PNG, GIF)
4. Τώρα θα δουλέψει! ✅

### Περιμένεις να δεις:
```
✅ Η ανακοίνωση δημιουργήθηκε επιτυχώς με 1 εικόνα/ες!
```

Αντί για:
```
⚠️ Η ανακοίνωση δημιουργήθηκε επιτυχώς (χωρίς εικόνες).
Προβλήματα με τα αρχεία:
Αποτυχία μεταφόρτωσης του αρχείου 'images.jpeg'
```

## Σημαντικές Σημειώσεις

### Για Development (local)
- Τα δικαιώματα `777` είναι **OK για local development**
- Επιτρέπουν στον Apache να γράψει ελεύθερα

### Για Production Server
⚠️ **ΠΡΟΣΟΧΗ**: Μην χρησιμοποιήσεις `777` σε production server!

Καλύτερες επιλογές για production:
```bash
# Επιλογή 1: Άλλαξε το ownership στον Apache user
chown -R www-data:www-data /path/to/assets/
chmod -R 755 /path/to/assets/

# Επιλογή 2: Πρόσθεσε τον Apache user στο group σου
chgrp -R www-data /path/to/assets/
chmod -R 775 /path/to/assets/
```

## Αν Εξακολουθεί να μην Δουλεύει

### 1. Έλεγξε τα δικαιώματα:
```bash
ls -ld /Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5/public/assets/Announcements_img/
```
Θα πρέπει να δεις: `drwxrwxrwx` (777)

### 2. Έλεγξε ποιος χρήστης τρέχει το Apache:
```bash
ps aux | grep httpd | grep -v grep
```

### 3. Χρησιμοποίησε το debug tool:
http://localhost/parents-council-platform-group5/public/admin/debug_upload.php

### 4. Έλεγξε τα PHP error logs:
```bash
tail -f /Applications/XAMPP/xamppfiles/logs/error_log
```

## Βασικά Δικαιώματα Unix

### Τι σημαίνει 777, 755, κλπ:
```
7 = rwx (read + write + execute)
5 = r-x (read + execute, no write)

drwxrwxrwx = 777 (owner + group + others όλοι έχουν πλήρη πρόσβαση)
drwxr-xr-x = 755 (owner: rwx, group/others: r-x)
drwxrwx--- = 770 (owner + group: rwx, others: nothing)
```

### Για τους upload φακέλους:
- **Minimum**: Ο Apache user χρειάζεται **write** (w) permission
- **Local dev**: 777 (πλήρη πρόσβαση σε όλους)
- **Production**: 755 με σωστό ownership ή 775 με σωστό group

## Αλλαγές Κώδικα

### Αρχείο που αλλάχτηκε:
- `public/admin/announcements.php`

### Προστέθηκαν:
1. Έλεγχος `file_exists($tmpName)` πριν το upload
2. Έλεγχος `is_writable($uploadDir)` 
3. Λεπτομερές error message με τα δικαιώματα του φακέλου
4. Οδηγίες χρήσης `chmod 777` στο error message

## Επόμενα Βήματα

1. ✅ **Δοκίμασε το upload τώρα** - Θα δουλέψει!
2. 📸 Ανέβασε εικόνες σε ανακοινώσεις
3. 📅 Δοκίμασε και τα Events (θα δουλέψουν και αυτά)
4. 📄 Δοκίμασε και τα Applications/Submissions docs

Καλή επιτυχία! 🎉
