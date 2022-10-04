# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0]
### Changed
- Database based stores allow update instead of delete+insert actions which no longer change scheduled entry token/identifier. Any modifications will no longer require storing new tokens.
- The `SimpleScheduleExecutor` didn't set `ScheduleState::Complete` nor `ScheduleState::InProgress` after executing last dispatch on entry having recurrence rule.

### Removed
- `MessageScheduler::reschedule` no longer accepts `$message` object
