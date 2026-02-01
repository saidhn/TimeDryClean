<!--
Sync Impact Report:
- Version change: 0.0.0 → 1.0.0 (initial constitution)
- Modified principles: None (new constitution)
- Added sections: Core Principles, Technology Stack Requirements, Development Workflow, Governance
- Removed sections: None (new constitution)
- Templates requiring updates: ✅ plan-template.md, ✅ tasks-template.md, ✅ spec-template.md
- Follow-up TODOs: None
-->

# TimeDryClean Constitution

## Core Principles

### I. Laravel-First Architecture
Every feature MUST leverage Laravel's ecosystem and conventions. Controllers handle requests, Models manage data, and Views present interfaces. All code MUST follow Laravel best practices and utilize built-in features like Eloquent, migrations, and routing.

### II. Multi-Role User System (NON-NEGOTIABLE)
System MUST support four distinct user types: client, driver, employee, and admin. Each role has specific permissions and workflows. Role-based access control MUST be enforced at both route and model levels.

### III. Arabic Localization
All user-facing interfaces MUST support Arabic language (RTL layout) and use Arabic Faker for test data. English support MUST be maintained for development and administrative interfaces. Date/time formats MUST respect Arabic conventions.

### IV. Service-Oriented Design
Business logic MUST be encapsulated in Service classes. Controllers remain thin, focusing only on HTTP concerns. Services handle complex operations like order processing, payment handling, and notification management.

### V. Mobile-Responsive Frontend
All interfaces MUST be fully responsive and optimized for mobile devices. Bootstrap 5 MUST be used for styling with custom RTL support. Touch interactions MUST be prioritized for driver and client interfaces.

## Technology Stack Requirements

- **Backend**: Laravel 11.x with PHP 8.2+
- **Database**: MySQL with proper migrations and seeders
- **Frontend**: Bootstrap 5 + jQuery + Vite for asset compilation
- **Testing**: PHPUnit for unit tests, Browser tests for critical workflows
- **Communication**: Twilio SDK for SMS notifications
- **Deployment**: Standard Laravel deployment with environment-based configuration

## Development Workflow

All features MUST follow this sequence:
1. Create feature specification using `/speckit.specify`
2. Generate implementation plan using `/speckit.plan`
3. Create actionable tasks using `/speckit.tasks`
4. Execute implementation using `/speckit.implement`

Code reviews MUST verify:
- Laravel conventions compliance
- Arabic language support
- Role-based access control
- Mobile responsiveness
- Test coverage for critical paths

## Governance

This constitution supersedes all other development practices. Amendments require:
- Documentation of proposed changes
- Team approval via pull request review
- Version increment following semantic versioning
- Update of all dependent templates and documentation

All pull requests MUST verify constitutional compliance. Complexity beyond standard patterns MUST be explicitly justified in the complexity tracking section of implementation plans.

**Version**: 1.0.0 | **Ratified**: 2026-02-01 | **Last Amended**: 2026-02-01
