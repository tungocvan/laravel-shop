You are acting as:

- Senior Software Architect
- Senior Laravel 12 Architect
- Senior Livewire 3 Engineer
- Senior Frontend Architect
- Senior Tailwind CSS 4 Expert
- Senior UI/UX Architect
- Technical Documentation Writer

This task is DOCUMENTATION ONLY.

Do NOT modify any application code.

Do NOT rewrite any Blade files.

Your task is to create a complete documentation project for the Admin Layout architecture.

--------------------------------------------------
Read first
--------------------------------------------------

docs/CODEX_BOOTSTRAP.md

docs/AI_PROJECT_CONTEXT.md

docs/PROJECT_BOOTSTRAP.md

ROADMAP.md

Then analyze

Modules/Admin/resources/views/layouts/master.blade.php

and any related partials/components that are used by this layout.

--------------------------------------------------
Objective
--------------------------------------------------

Create a new documentation directory

docs/admin-layout/

This documentation will become the single source of truth for every future Admin Layout refactor.

The documents should be written in professional Markdown.

Use headings, tables, diagrams (Mermaid where appropriate), checklists, and architecture explanations.

--------------------------------------------------
Generate the following files
--------------------------------------------------

docs/admin-layout/

├── README.md
├── LAYOUT_ANALYSIS.md
├── CURRENT_ARCHITECTURE.md
├── TARGET_ARCHITECTURE.md
├── COMPONENT_TREE.md
├── RESPONSIVE_STRATEGY.md
├── CONFIGURATION_SPEC.md
├── DESIGN_SYSTEM.md
├── UI_GUIDELINES.md
├── ACCESSIBILITY.md
├── PERFORMANCE.md
├── LIVEWIRE_GUIDE.md
├── BLADE_COMPONENT_GUIDE.md
├── REFACTOR_PLAN.md
├── REBUILD_SPEC.md
├── IMPLEMENTATION_ROADMAP.md
├── CHECKLIST.md
├── DECISIONS.md
└── CHANGELOG.md

--------------------------------------------------
README.md
--------------------------------------------------

Explain:

Purpose

Goals

Scope

How developers should use this documentation

Recommended workflow

Required reading order

--------------------------------------------------
LAYOUT_ANALYSIS.md
--------------------------------------------------

Analyze

current layout

strengths

weaknesses

technical debt

maintainability

scalability

readability

Blade quality

Tailwind quality

Responsive quality

Accessibility

Performance

UI consistency

--------------------------------------------------
CURRENT_ARCHITECTURE.md
--------------------------------------------------

Describe current layout architecture.

Include Mermaid diagrams for:

Page hierarchy

Blade includes

Component relationships

--------------------------------------------------
TARGET_ARCHITECTURE.md
--------------------------------------------------

Design the ideal architecture.

Include:

Layouts

Partials

Blade Components

Livewire Components

Theme Layer

Configuration Layer

Navigation Layer

Widget Layer

--------------------------------------------------
COMPONENT_TREE.md
--------------------------------------------------

Design a complete component tree.

Example

Master Layout

Header

Top Navigation

Sidebar

Sidebar Item

Sidebar Group

Breadcrumb

Page Header

Toolbar

Flash Messages

Notifications

Content

Footer

Modal Stack

Toast Stack

Loading Overlay

Drawer

Search Overlay

Mobile Navigation

Theme Switcher

User Menu

Profile Menu

Etc.

--------------------------------------------------
RESPONSIVE_STRATEGY.md
--------------------------------------------------

Define

Desktop

Laptop

Tablet

Mobile

Sidebar behaviour

Drawer behaviour

Header

Tables

Cards

Forms

Grid system

Touch spacing

Breakpoints

Landscape mode

Safe areas

--------------------------------------------------
CONFIGURATION_SPEC.md
--------------------------------------------------

Design a configuration system.

Include every configurable option.

Examples

Sidebar

Header

Theme

Container width

Animations

Compact mode

Sticky elements

RTL

Dark mode

Breadcrumb

Footer

Accent colors

Layout presets

User preferences

Role-based layout

State persistence

Explain where each configuration should live.

config/admin.php

database

cache

session

service classes

--------------------------------------------------
DESIGN_SYSTEM.md
--------------------------------------------------

Define the Admin Design System.

Typography

Spacing

Colors

Radius

Shadow

Buttons

Forms

Tables

Cards

Badges

Alerts

Dropdowns

Navigation

Icons

Charts

Empty states

Loading states

Skeletons

Responsive rules

--------------------------------------------------
UI_GUIDELINES.md
--------------------------------------------------

Document UI principles.

Consistency

Spacing

Hierarchy

Visual balance

SaaS Admin standards

--------------------------------------------------
ACCESSIBILITY.md
--------------------------------------------------

WCAG recommendations

ARIA

Keyboard navigation

Focus management

Semantic HTML

Contrast

Screen reader support

--------------------------------------------------
PERFORMANCE.md
--------------------------------------------------

Render optimization

DOM optimization

Asset loading

Lazy loading

Deferred scripts

CSS strategy

Caching

Livewire optimization

--------------------------------------------------
LIVEWIRE_GUIDE.md
--------------------------------------------------

Best practices for

wire:navigate

Events

Teleport

Polling

Lazy loading

Forms

Modals

Notifications

--------------------------------------------------
BLADE_COMPONENT_GUIDE.md
--------------------------------------------------

Rules for creating Blade Components.

Naming

Folder structure

Slots

Props

Reusable design

--------------------------------------------------
REFACTOR_PLAN.md
--------------------------------------------------

Create a step-by-step refactor strategy.

No code.

--------------------------------------------------
REBUILD_SPEC.md
--------------------------------------------------

Write a complete specification for rebuilding the layout.

No code.

--------------------------------------------------
IMPLEMENTATION_ROADMAP.md
--------------------------------------------------

Split the rebuild into phases.

Estimate

Difficulty

Risk

Impact

Dependencies

--------------------------------------------------
CHECKLIST.md
--------------------------------------------------

A developer checklist before merging.

Architecture

UI

Responsive

Accessibility

Performance

Testing

--------------------------------------------------
DECISIONS.md
--------------------------------------------------

Record architectural decisions.

Include ADR (Architecture Decision Record) format.

--------------------------------------------------
CHANGELOG.md
--------------------------------------------------

Prepare a changelog template for future layout updates.

--------------------------------------------------
Output Requirements
--------------------------------------------------

Create every Markdown file with detailed, production-quality documentation.

Do not generate placeholder text.

Do not modify application code.

The documentation should be comprehensive enough that another AI or developer can rebuild the Admin Layout entirely from these documents without needing additional clarification.