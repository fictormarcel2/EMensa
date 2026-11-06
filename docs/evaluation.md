# Evaluation protocol

This document separates automated product verification from a participant study. No student sessions or questionnaire scores have been collected by the implementation agent.

## Formative sessions

Recruit approximately five students for formative feedback, subject to the course rubric. Explain that accounts, menu prices and dishes are fictional evaluation data, that counts are submitted plans rather than crowd measurements, and that the app does not track physical location. Obtain consent before recording a session; use participant codes rather than student identifiers.

Use a future weekday with valid service hours, so saving and editing are possible. Choose Eupener Straße for the service-break task. Use separate fictional accounts for the friendship and privacy tasks. Do not grant browser notification or camera permission on a participant's behalf.

| Task | Observable success criterion |
| --- | --- |
| Find lunch times as a guest | Select the requested mensa/date and explain the meaning of a submitted-plan count. |
| Choose one arrival time | Select 12:30, then 13:00; exactly one option remains selected and nothing is saved yet. |
| Confirm a plan | Set an optional meeting point, choose an audience and confirm; the saved summary matches after reload. |
| Change and cancel | Edit the arrival time and cancel; counts change once and another account's plan remains intact. |
| Add a friend | Preview the alias from a code, send a request and accept from the other account. Manual entry works without a camera. |
| Revoke sharing | Share with an accepted friend, then switch to private; the other account receives only `No shared details`. |
| Follow a web link | Open the mensa/date/time context; one eligible draft time is selected without automatically saving. |
| Understand opening hours | Explain why 14:15 on a Thursday and 14:00 on a Friday are unavailable at Eupener. |

For each task record success, elapsed time, errors, assistance and a short participant explanation. Treat failure as evidence about the interface, not the participant. Record browser, viewport, input method and build revision. After the whole flow, ask about clarity, trust in sharing settings and the most confusing step. If the course requires SUS, use the required standard instrument and score it only after collecting real answers.

## Result form

| Participant code | Task | Success | Seconds | Errors/assistance | Observation |
| --- | --- | --- | --- | --- | --- |

No entries have been collected. Do not fill this table with generated participants or estimated timings.

## Automated evidence

See [execution record](../.planning/progress.md) for actual test commands, failures fixed and measured outcomes. Automated checks cover behavior and technical accessibility; they do not establish that real students understand the product or that reminders have arrived on a physical device.

Native mobile and home-screen widgets are excluded by the user's web-only scope decision.
