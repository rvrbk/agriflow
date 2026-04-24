# Agriflow Documentation

## Overview

This directory contains comprehensive documentation for the Agriflow project, covering architecture, API specifications, code style guidelines, database schema, and team structure.

## Documentation Structure

```
docs/
├── README.md                    # This file - Documentation index
│
├── architecture/                # Technical Architecture Documentation
│   ├── SYSTEM_ARCHITECTURE.md   # High-level system architecture overview
│   ├── API_DOCUMENTATION.md     # Complete API endpoint documentation
│   ├── DATABASE_SCHEMA.md       # Database design and ERD
│   └── CODE_STYLE_GUIDE.md      # Code style conventions and best practices
│
└── team/                       # Team & Organization Documentation
    └── TEAM_STRUCTURE.md         # Team roles, responsibilities, and workflows
```

## Quick Navigation

### Architecture & Technical Documentation

| Document | Description | Primary Audience |
|----------|-------------|------------------|
| [SYSTEM_ARCHITECTURE.md](./architecture/SYSTEM_ARCHITECTURE.md) | System architecture, component diagrams, data flow | Developers, Architects, New Team Members |
| [API_DOCUMENTATION.md](./architecture/API_DOCUMENTATION.md) | REST API endpoints, authentication, request/response examples | Developers, QA, API Consumers |
| [DATABASE_SCHEMA.md](./architecture/DATABASE_SCHEMA.md) | Entity-Relationship Diagram, table definitions, constraints | Backend Devs, DBAs, QA |
| [CODE_STYLE_GUIDE.md](./architecture/CODE_STYLE_GUIDE.md) | PHP, JavaScript/Vue, Laravel, Testing style guide | All Developers |

### Team & Process Documentation

| Document | Description | Primary Audience |
|----------|-------------|------------------|
| [TEAM_STRUCTURE.md](./team/TEAM_STRUCTURE.md) | Team roles, hierarchy, workflows, communication norms | All Team Members, Managers |

## Getting Started

### For New Developers

1. **Read System Architecture** to understand the overall design
2. **Review API Documentation** to understand available endpoints
3. **Study Database Schema** to understand data relationships
4. **Follow Code Style Guide** for all development work
5. **Check Team Structure** to understand processes and workflows

### For Specific Tasks

#### Adding a New Feature
```
1. Review SYSTEM_ARCHITECTURE.md
2. Check API_DOCUMENTATION.md for similar endpoints
3. Follow patterns in CODE_STYLE_GUIDE.md
4. Update DATABASE_SCHEMA.md if new tables/columns
5. Update API_DOCUMENTATION.md with new endpoints
```

#### Debugging an Issue
```
1. Check SYSTEM_ARCHITECTURE.md for component relationships
2. Review DATABASE_SCHEMA.md for data relationships
3. Consult API_DOCUMENTATION.md for expected behavior
4. Follow debugging practices in CODE_STYLE_GUIDE.md
```

#### Onboarding New Team Member
```
1. Share TEAM_STRUCTURE.md
2. Walk through SYSTEM_ARCHITECTURE.md
3. Review CODE_STYLE_GUIDE.md together
4. Provide access to all documentation
```

## Document Maintenance

### Update Frequency

| Document | Update Trigger | Responsible Team |
|----------|----------------|------------------|
| SYSTEM_ARCHITECTURE.md | Major architectural changes | Backend/Dev Team |
| API_DOCUMENTATION.md | New/changed endpoints | Backend Dev Team |
| DATABASE_SCHEMA.md | Schema migrations | Backend Dev Team |
| CODE_STYLE_GUIDE.md | Style guide updates | All Devs |
| TEAM_STRUCTURE.md | Team changes | Orchestrator |

### Contribution Guidelines

1. **Keep documentation up-to-date** with code changes
2. **Review documentation** as part of code review process
3. **Use diagrams and examples** where helpful
4. **Maintain consistency** across documents
5. **Include version information** where relevant
6. **Link between documents** for easy navigation

## Version Information

| Document | Last Updated | Version | Author |
|----------|--------------|---------|--------|
| SYSTEM_ARCHITECTURE.md | April 13, 2026 | 1.0 | AI Assistant |
| API_DOCUMENTATION.md | April 13, 2026 | 1.0 | AI Assistant |
| DATABASE_SCHEMA.md | April 13, 2026 | 1.0 | AI Assistant |
| CODE_STYLE_GUIDE.md | April 13, 2026 | 1.0 | AI Assistant |
| TEAM_STRUCTURE.md | April 13, 2026 | 1.0 | AI Assistant |

## Contact

For questions about documentation:
- **Technical Architecture**: @david (Backend Lead) or @frank (Frontend Lead)
- **API Documentation**: @david (Backend Lead)
- **Team Structure**: @alice (Orchestrator)
- **Code Style**: @david (Backend Lead) or @frank (Frontend Lead)

## Quick Links

- [System Architecture](./architecture/SYSTEM_ARCHITECTURE.md)
- [API Documentation](./architecture/API_DOCUMENTATION.md)
- [Database Schema](./architecture/DATABASE_SCHEMA.md)
- [Code Style Guide](./architecture/CODE_STYLE_GUIDE.md)
- [Team Structure](./team/TEAM_STRUCTURE.md)
- [Main Project README](../README.md)

## Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [Vue.js Documentation](https://vuejs.org/guide/)
- [Tailwind CSS Documentation](https://tailwindcss.com/docs)
- [Pinia Documentation](https://pinia.vuejs.org/)
- [Laravel Sanctum Documentation](https://laravel.com/docs/sanctum)

---

## Document Conventions

### Format
- Use Markdown for all documentation
- Include table of contents for long documents
- Use code blocks for examples
- Include diagrams using ASCII art or Mermaid
- Use tables for structured data

### Style
- Be concise but comprehensive
- Use bullet points for lists
- Bold important terms
- Use consistent heading hierarchy
- Include practical examples

### Structure
1. Overview/Introduction
2. Detailed Content
3. Examples
4. Best Practices
5. References/Links

---

*Last updated: April 13, 2026*
*Maintained by: Agriflow Development Team*
