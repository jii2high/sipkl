# AGENTS.md

## Project
Sistem Informasi Manajemen PKL SMKN 1 Kota Bekasi. Planning/spec repo: no application code yet. Source of truth order: `docs/PRD.md` (behavior) -> `docs/TRD.md` (implementation) -> `docs/schema.sql` (data shape) -> `Design.md` (visual).

## Stack (from TRD)
PHP/Laravel + Filament multi-panel (`app`, `hubin`), Livewire/Blade, Tailwind, MySQL 8.0.16+, database queue/cache/session, private local storage. No extra packages or abstractions beyond TRD without a task.

## Non-obvious rules
- All business rules and state transitions live in `app/Actions`, `app/Policies`, `app/Services`; UI only calls them.
- Status changes only through Actions, in one transaction with audit event.
- Timestamps UTC; calendar dates Asia/Jakarta.
- Queue/cache/session use `database`, not Redis.
- `docs/schema.sql` is design reference, not a migration script.
- Reference folders have spaces (`docs/old physical document examples/`, `docs/form-penilaian-references/`); quote paths in shell.
- UI text and validation messages are Bahasa Indonesia.

## OpenCode setup
- Project config: `.opencode/opencode.jsonc` (`plugin`: `./plugins/caveman`, `@dietrichgebert/ponytail`).
- Skills also in `.agents/skills` (compatibility source).
- Use installed skills/plugins in every prompt/session.
- Anti-slop mode: DURING.

## Skills to apply
- `antislop` core always; add `antislop-ui`, `antislop-code`, `antislop-copywriting`, `antislop-human`, `antislop-layoutmobile` when relevant.
- `caveman`/`ponytail` for terse, minimal-change work.
- `i-have-adhd` output shaping when useful.
- `ui-ux-pro-max` and related design skills for UI work.
<!-- caveman-begin -->
Respond terse like smart caveman. All technical substance stay. Only fluff die.

Rules:
- Answer first: Answer, then reason, then next step.
- Kill ceremony: No greeting, hedging, pleasantries, recap, or closer.
- Short word: "fix" not "implement a solution for".
- Articles optional, meaning never: Drop a/an/the when the sentence still reads in one pass.
- One idea per sentence: ASD-STE100 is the floor: 20 words max, active voice, imperative for instructions, one term per thing, pronoun only with an obvious referent.
- Payload verbatim: Code blocks unchanged.
- Tool runs: bounded status: No text between routine calls.
- User's language: Compress the style, not the language.
- Never perform caveman: No "caveman mode on", no "me think", no "Caveman:" prefix, no normal answer plus caveman copy.

Switch: /caveman (default), /ultracave (fragments, each fact once), /megacave (Classical Chinese 文言文)
Stop: "stop caveman" or "normal mode"

Auto-Clarity: plain prose for security warnings, irreversible actions, step order a fragment could scramble, user confused, questions you ask the user. Resume after.

Boundaries: code, comments, commits, PRs, docs written normal; existing comments kept unless asked.
Floor: code, commands, paths, numbers and error strings verbatim; never drop not/never/no/only.
<!-- caveman-end -->