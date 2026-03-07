#!/bin/bash

# Script διόρθωσης δικαιωμάτων για uploads
# Βάζει σωστά δικαιώματα σε όλους τους φακέλους που δέχονται αρχεία

echo "🔧 Fixing upload directory permissions..."
echo ""

BASE_DIR="/Applications/XAMPP/xamppfiles/htdocs/parents-council-platform-group5"
ASSETS_DIR="$BASE_DIR/public/assets"

# Λίστα φακέλων που πρέπει να έχουν δικαίωμα εγγραφής
UPLOAD_DIRS=(
    "Announcements_img"
    "Events_img"
    "Applications_docs"
    "Submissions_docs"
    "Products_img"
    "Posts_img"
)

echo "📁 Setting permissions for upload directories..."
echo ""

for dir in "${UPLOAD_DIRS[@]}"; do
    DIR_PATH="$ASSETS_DIR/$dir"
    
    if [ -d "$DIR_PATH" ]; then
        echo "  ✓ Setting 777 on $dir/"
        chmod 777 "$DIR_PATH"
    else
        echo "  ⚠ Directory not found: $dir/"
    fi
done

echo ""
echo "✅ Done! Checking results..."
echo ""

# Εμφάνιση αποτελέσματος για κάθε φάκελο
for dir in "${UPLOAD_DIRS[@]}"; do
    DIR_PATH="$ASSETS_DIR/$dir"
    if [ -d "$DIR_PATH" ]; then
        PERMS=$(ls -ld "$DIR_PATH" | awk '{print $1}')
        echo "  $dir: $PERMS"
    fi
done

echo ""
echo "🎉 All upload directories are now writable!"
echo ""
echo "You can now upload files through the admin panel."
echo ""
