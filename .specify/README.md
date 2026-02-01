# SpecKit Workflow System

This directory contains the SpecKit workflow system for managing feature specifications and implementation planning.

## Directory Structure

```
.specify/
├── scripts/
│   └── powershell/
│       ├── create-new-feature.ps1  # Creates new feature branches and spec structure
│       └── setup-speckit.ps1       # Initial setup script
├── templates/
│   └── spec-template.md            # Template for feature specifications
└── README.md                       # This file

specs/
└── [N-feature-name]/               # Feature-specific directories
    ├── spec.md                     # Feature specification
    ├── plan.md                     # Implementation plan
    ├── tasks.md                    # Task breakdown
    ├── checklists/                 # Quality checklists
    └── artifacts/                  # Supporting documents
```

## Setup

Run the setup script to initialize the SpecKit system:

```powershell
.\.specify\scripts\powershell\setup-speckit.ps1
```

## Workflows

### `/speckit.specify <feature description>`
Creates a new feature specification from a natural language description.

**Example:**
```
/speckit.specify Add user authentication with OAuth2 support
```

### `/speckit.clarify`
Identifies underspecified areas and asks targeted clarification questions.

### `/speckit.plan`
Generates a detailed implementation plan from the specification.

### `/speckit.tasks`
Breaks down the plan into actionable, dependency-ordered tasks.

### `/speckit.implement`
Executes the implementation by processing tasks.

## Feature Numbering

Features are automatically numbered based on existing branches and specs:
- Format: `N-short-name` (e.g., `1-user-auth`, `2-payment-integration`)
- Numbers are auto-incremented to avoid conflicts
- Checks remote branches, local branches, and specs directories

## Templates

### Specification Template
Located at `.specify/templates/spec-template.md`, this template ensures all specifications include:
- Overview and user value
- User scenarios and testing criteria
- Functional requirements
- Success criteria (measurable, technology-agnostic)
- Key entities and dependencies
- Constraints and open questions

## Best Practices

1. **Keep specs technology-agnostic** - Focus on what, not how
2. **Make success criteria measurable** - Use quantitative metrics
3. **Limit clarifications** - Maximum 3 per spec, prioritize by impact
4. **Write testable requirements** - Each requirement must have acceptance criteria
5. **Document assumptions** - Make implicit knowledge explicit

## Troubleshooting

### Script execution policy error
If you get an execution policy error, run:
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

### Git not found
Ensure git is installed and available in your PATH.

### Permission errors
Run PowerShell as Administrator if you encounter permission issues.

## Integration with Windsurf

SpecKit workflows are integrated with Windsurf's workflow system via `.windsurf/workflows/` directory. The workflows can be triggered using slash commands in the Cascade chat.
