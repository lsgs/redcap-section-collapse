********************************************************************************
# REDCap External Module: Section Collapse

Luke Stevens, Murdoch Children's Research Institute https://www.mcri.edu.au

[https://github.com/lsgs/redcap-section-collapse](https://github.com/lsgs/redcap-section-collapse)
********************************************************************************
## Summary

Adds a toggle switch to section headers on data entry and survey forms that can be used for showing and hiding all fields within the section.

********************************************************************************
## Modes

* By Section: each section can be expanded or collapsed independently.
* Accordion: only one section at a time can be expanded - expand one section and all others collapse.

********************************************************************************
## Configuration

A project-level default setting can be specfied:
* No selection (default): both accordion and by section modes available
* Accordion mode only
* By Section mode only
* Neither (both disabled)

Instrument-level settings can be specified to override the project-level setting for individual instruments:
* Both accordion and by section modes available
* Accordion mode only
* By Section mode only
* Neither (both disabled)

********************************************************************************
## Mode Preference Persistence
Where both modes are available, the user's preference is persisted. The mechanism depends on the form view:
* Data entry: via user-level setting
* Survey: via cookie

********************************************************************************