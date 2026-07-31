# Contacts Feature - CSV Import

## Overview
CSV import functionality allows users to import contacts from CSV files into the app.

## Folder Structure
```
contacts/
├── screens/
│   ├── contact_groups_screen.dart  # Main contacts screen
│   └── csv_import_screen.dart      # CSV import UI
├── services/
│   └── csv_import_service.dart     # CSV parsing logic
└── README.md
```

## CSV File Format

### Supported Formats (Flexible!)

The CSV import now supports **multiple formats** automatically:

#### 1. Standard Format (Name, Phone)
```csv
Name,Phone Number
Rahul Kumar,+919876543210
Priya Sharma,9876543211
Amit Patel,+919876543212
```

#### 2. Phone Numbers Only
```csv
+919876543210
9876543211
+919876543212
```
*Names will be auto-generated as "Contact XXXX"*

#### 3. Reversed Format (Phone, Name)
```csv
Phone Number,Name
+919876543210,Rahul Kumar
9876543211,Priya Sharma
```

#### 4. Mixed Format
```csv
Phone
+919876543210
9876543211
Amit,+919876543212
Priya Sharma,9876543214
```

### Features
- ✅ **Automatic format detection** - Works with any column order
- ✅ **Smart phone number detection** - Finds numbers in any column
- ✅ **Automatic header detection** - Skips header rows automatically
- ✅ **Phone number normalization** - Removes spaces, dashes, brackets
- ✅ **Auto-generated names** - Creates names if not provided
- ✅ **Validation** - Minimum 8 digits required
- ✅ **Preview before import**
- ✅ **Group naming**
- ✅ **Error handling**

## Usage

1. Navigate to Contact Groups screen
2. Click on "CSV" import icon
3. Select your CSV file
4. Preview the contacts
5. Enter a group name
6. Click "Import Contacts"

## Dependencies
- `csv: ^6.0.0` - CSV parsing
- `file_picker: ^8.1.6` - File selection

## Sample Files
Multiple sample CSV files are available for testing:
- `sample_contacts.csv` - Standard format (Name, Phone)
- `sample_contacts_numbers_only.csv` - Only phone numbers
- `sample_contacts_mixed.csv` - Mixed format with and without names
- `sample_contacts_reversed.csv` - Reversed format (Phone, Name)
