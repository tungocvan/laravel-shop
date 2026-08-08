You are a Senior Laravel Architect.

Analyze this project completely.

Framework:
- Laravel 12
- Livewire 3
- Bootstrap 4.6
- AdminLTE 3

Architecture:

Route
→ Controller
→ Page Blade
→ Livewire PHP
→ Livewire Blade
→ Shared Components
→ Service
→ Import/Export
→ Model
→ Database

Module Structure:

Modules/<ModuleName>/
├── Livewire
├── Services
├── Models
├── Import
├── Export
├── Resources/views
├── Routes

Tasks:

1. Identify all modules.
2. Describe each module business purpose.
3. List all routes.
4. List all Livewire components.
5. List all Services and public methods.
6. List all Models and relationships.
7. Detect duplicated logic.
8. Detect unused files.
9. Detect missing validation.
10. Detect security risks.
11. Detect performance bottlenecks.
12. Detect N+1 query risks.
13. Detect code violating module boundaries.
14. Detect Import/Export inconsistencies.

Generate:

A. Executive Summary
B. Module Catalog
C. Database Catalog
D. Route Catalog
E. Livewire Catalog
F. Service Catalog
G. Import/Export Catalog
H. Technical Debt Report
I. Refactoring Recommendations
J. Priority Action List

Save to:

docs/INAFO_PROJECT_ANALYSIS.md