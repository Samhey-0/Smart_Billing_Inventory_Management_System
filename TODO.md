# Inspire Shoes Billing System - TODO List

## Phase 1: Database & Configuration
- [ ] Create database/inspire_shoes.sql (schema + seed data)
- [ ] Create config/db.php (PDO connection with security settings)

## Phase 2: Core Includes
- [ ] Create includes/functions.php (reusable utility functions)
- [ ] Create includes/header.php (global HTML header + nav)
- [ ] Create includes/footer.php (global HTML footer)

## Phase 3: Authentication
- [ ] Create auth/register.php (admin registration - first-time setup)
- [ ] Create auth/login.php (login with security features)
- [ ] Create auth/logout.php (destroy session)

## Phase 4: Assets (CSS & JS)
- [ ] Create assets/css/style.css (main stylesheet - Navy Blue & Gold theme)
- [ ] Create assets/css/print.css (print-specific styles)
- [ ] Create assets/js/app.js (JavaScript logic for cart, validation, AJAX)

## Phase 5: Product Management
- [ ] Create products/list.php (view all products with search)
- [ ] Create products/add.php (add new product with image upload)
- [ ] Create products/edit.php (edit existing product)
- [ ] Create products/delete.php (delete product)

## Phase 6: Customer Management
- [ ] Create customers/list.php (view all customers with search)
- [ ] Create customers/add.php (add new customer)
- [ ] Create customers/edit.php (edit customer details)
- [ ] Create customers/delete.php (delete customer with warning)
- [ ] Create customers/profile.php (customer details + purchase history)

## Phase 7: Invoice/Billing System
- [ ] Create invoices/list.php (view all invoices)
- [ ] Create invoices/create.php (POS-style invoice creation with cart)
- [ ] Create invoices/view.php (view single invoice details)
- [ ] Create invoices/receipt.php (printable receipt)

## Phase 8: Dashboard
- [ ] Create index.php (dashboard with statistics cards + recent invoices)

## Phase 9: Setup Instructions
- [ ] Create SETUP.md (step-by-step installation instructions)

## Security Features to Implement (as per requirements):
- [ ] PDO Prepared Statements for all queries
- [ ] htmlspecialchars() on all output
- [ ] CSRF tokens on all forms
- [ ] File upload validation (MIME type, size limit, UUID renaming)
- [ ] Session security settings
- [ ] Input sanitization with filter_input() and trim()
- [ ] Authorization checks for delete operations
