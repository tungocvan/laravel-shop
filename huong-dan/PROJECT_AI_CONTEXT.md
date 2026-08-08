# PROJECT ANALYSIS MODE

Analyze the entire source code repository and generate a complete markdown document:

`PROJECT_SUMMARY.md`

Requirements:

## 1. Executive Summary

* Project name
* Main purpose
* Business domain
* Core features
* Target users

## 2. Technology Stack

Detect and document:

* PHP version
* Laravel version
* Livewire version
* AlpineJS
* TailwindCSS
* Bootstrap
* MySQL
* Redis
* Queue
* Vite
* NodeJS
* Third-party packages

Create a dependency table.

## 3. Architecture Overview

Analyze architecture and generate diagrams in markdown:

* Route → Controller → View
* Route → Livewire → Service → Model
* Module structure
* Shared services
* Traits
* Helpers

Explain actual project architecture.

## 4. Module Inventory

Detect all modules and generate:

| Module | Purpose | Models | Services | Livewire |
| ------ | ------- | ------ | -------- | -------- |

For each module describe:

* Business purpose
* Main workflows
* Dependencies
* Import/Export support

## 5. Database Analysis

Generate:

### Tables

* Table name
* Purpose
* Row estimate (if possible)

### Relationships

* belongsTo
* hasMany
* manyToMany

Create ERD in Mermaid.

## 6. Livewire Analysis

Detect all Livewire components.

For each component:

* Purpose
* Public properties
* Events
* Validation rules
* Services used

## 7. Service Layer Analysis

Analyze:

* Service classes
* Responsibilities
* Potential violations of SRP
* Duplicate logic

List refactoring opportunities.

## 8. Import / Export Analysis

Detect:

* FastExcel usage
* Laravel Excel usage
* CSV importers

Document:

* Import classes
* Export classes
* Shared foundation

Suggest improvements.

## 9. Security Review

Check:

* Authorization
* Validation
* File upload risks
* XSS risks
* SQL injection risks
* Mass assignment risks

Generate findings.

## 10. Performance Review

Check:

* N+1 queries
* Missing eager loading
* Large collections
* Pagination issues
* Memory heavy imports

Generate recommendations.

## 11. Code Quality Review

Detect:

* Dead code
* Duplicate code
* Long methods
* Large services
* Large Livewire components

Rank:

Critical
High
Medium
Low

## 12. Development Guide

Generate:

### Local Installation

### Environment Variables

### Build Commands

### Queue Commands

### Scheduler Commands

## 13. Suggested Refactoring Roadmap

Create roadmap:

Phase 1 - Quick Wins
Phase 2 - Architecture Cleanup
Phase 3 - Performance
Phase 4 - Scalability

## 14. Generate Files

Create:

* PROJECT_SUMMARY.md
* MODULES.md
* DATABASE.md
* REFACTORING_PLAN.md

Use professional markdown formatting.
Use tables wherever possible.
Use Mermaid diagrams.
Base analysis only on actual source code.
Do not invent functionality.
