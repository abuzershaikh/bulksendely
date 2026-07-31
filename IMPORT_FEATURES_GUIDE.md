# Contact Import Features - Complete Guide

## 📁 Folder Structure

```
lib/features/contacts/
├── csv_import/
│   ├── services/
│   │   └── csv_import_service.dart
│   └── screens/
│       └── csv_import_screen.dart
├── vcf_import/
│   ├── services/
│   │   └── vcf_import_service.dart
│   └── screens/
│       └── vcf_import_screen.dart
├── sheets_import/
│   ├── services/
│   │   └── sheets_import_service.dart
│   └── screens/
│       └── sheets_import_screen.dart
└── screens/
    └── contact_groups_screen.dart (Main screen)
```

## 🎯 Import Options

### 1. CSV Import (Purple Icon)
**File Format:** `.csv`

**Supported Formats:**
- Name, Phone Number
- Phone Number only (auto-generates names)
- Any column order (smart detection)
- Mixed formats

**Example:**
```csv
Name,Phone Number
Rahul Kumar,+919876543210
9876543211
Amit,+919876543212
```

**Features:**
- ✅ Flexible format detection
- ✅ Auto-generated names
- ✅ Header detection
- ✅ Phone number normalization

---

### 2. VCF Import (Blue Icon)
**File Format:** `.vcf` or `.vcard`

**Supported Versions:**
- vCard 2.1
- vCard 3.0
- vCard 4.0

**Example VCF:**
```
BEGIN:VCARD
VERSION:3.0
FN:Rahul Kumar
TEL;TYPE=CELL:+919876543210
END:VCARD
```

**Features:**
- ✅ Single or multiple contacts
- ✅ Standard vCard format
- ✅ Exported from phones/email clients
- ✅ Name and number extraction

---

### 3. Google Sheets Import (Green Icon)
**Source:** Google Sheets URL

**How to Use:**
1. Open your Google Sheet
2. Click "Share" button
3. Change to "Anyone with the link"
4. Set permission to "Viewer"
5. Copy link
6. Paste in app

**Sheet Format:**
```
Name          | Phone Number
Rahul Kumar   | +919876543210
Priya Sharma  | 9876543211
```

**Features:**
- ✅ Real-time import from cloud
- ✅ No file download needed
- ✅ Flexible format support
- ✅ Large dataset support

---

### 4. Phone Contacts Import (Teal Icon)
**Source:** Device contacts

**Features:**
- ✅ Direct access to phone contacts
- ✅ Permission-based
- ✅ Bulk import
- ✅ Name and number extraction

---

### 5. Text Import (Orange Icon)
**Coming Soon**

---

## 📦 Dependencies

```yaml
dependencies:
  csv: ^6.0.0              # CSV parsing
  file_picker: ^8.1.6      # File selection
  http: ^1.2.2             # Google Sheets API
  url_launcher: ^6.3.1     # URL handling
  flutter_contacts: ^2.0.2 # Phone contacts
  permission_handler: ^12.0.1 # Permissions
```

## 🚀 Usage Flow

1. **Open App** → Contact Groups Screen
2. **Select Import Method** → Click on icon (CSV/VCF/Sheets/Phone)
3. **Select Source** → File/URL/Phone
4. **Preview Contacts** → Review imported data
5. **Name Group** → Enter group name
6. **Import** → Save to app

## 📱 Sample Files

- `sample_contacts.csv` - Standard CSV format
- `sample_contacts_numbers_only.csv` - Only phone numbers
- `sample_contacts_mixed.csv` - Mixed format
- `sample_contacts_reversed.csv` - Reversed columns
- `sample_contacts.vcf` - VCF/vCard format

## 🎨 UI Features

### Import Icons
- **CSV** - Purple (description_rounded)
- **VCF** - Blue (insert_drive_file_rounded)
- **Sheets** - Green (grid_on_rounded)
- **Text** - Orange (paste_rounded)
- **Phone** - Teal (contacts_rounded)

### Preview Features
- Shows up to 50 contacts
- Displays name and number
- Color-coded avatars
- Total count indicator

### Error Handling
- Invalid file format
- Empty files
- Network errors (Sheets)
- Permission denied (Phone)

## 🔧 Technical Details

### CSV Import Service
- Flexible column detection
- Phone number regex validation
- Auto-name generation
- Header row detection

### VCF Import Service
- vCard parsing (2.1, 3.0, 4.0)
- FN and N field extraction
- TEL field parsing
- Multiple contact support

### Sheets Import Service
- URL to CSV conversion
- Google Sheets API integration
- Real-time data fetch
- Sheet ID extraction

## 🎯 Next Steps (Optional)

1. ✅ CSV Import - DONE
2. ✅ VCF Import - DONE
3. ✅ Google Sheets Import - DONE
4. ⏳ XLSX Import
5. ⏳ Text Paste Import
6. ⏳ Contact Editing
7. ⏳ Duplicate Detection
8. ⏳ Export Functionality

## 📝 Notes

- All imports support flexible phone number formats
- Names are auto-generated if not provided
- Preview shows first 50 contacts
- All data stored locally
- No cloud sync (privacy-focused)

## 🐛 Troubleshooting

### CSV Import Issues
- Ensure file has .csv extension
- Check for at least one phone number
- Verify phone numbers have 8+ digits

### VCF Import Issues
- Ensure file has .vcf or .vcard extension
- Check for BEGIN:VCARD and END:VCARD tags
- Verify TEL fields exist

### Google Sheets Issues
- Ensure sheet is shared publicly
- Check URL format
- Verify internet connection
- Ensure sheet has data

### Phone Import Issues
- Grant contacts permission
- Check if contacts exist on device
- Verify phone numbers are valid
