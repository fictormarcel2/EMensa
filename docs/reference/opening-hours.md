# Aachen mensa opening hours and slot policy

Checked: **5 November 2026**. Timezone: **Europe/Berlin**.

These are the operator's published **regular mensa service hours**, not a real-time guarantee that a building is open. Keep cafeteria/coffee-bar hours separate. The primary location in the source PDF is Mensa Eupener Straße.

## Regular Aachen mensa service windows

| Location | Monday-Thursday | Friday |
| --- | --- | --- |
| Mensa Eupener Straße | 11:15-14:15 and 14:30-17:00 | 11:15-14:00 |
| Mensa Bayernallee | 11:30-14:30 | 11:30-14:00 |
| Mensa KMAC | 11:30-14:30 | 11:30-14:00 |
| Mensa Südpark | 11:30-14:30 | 11:30-14:00 |
| Mensa Academica | 11:30-15:00 | 11:30-14:30 |
| Mensa Ahornstraße | 11:30-14:30 | 11:30-14:00 |
| Mensa Vita | 11:30-14:30 | 11:30-14:30 |
| Bistro Templergraben | 10:30-18:00 | 10:30-14:30 |

The published regular schedule does not list weekend mensa service. Jülich is outside Aachen city and is not silently added to the initial location scope.

Eupener's **cafeteria**, separately, is listed Monday-Thursday 08:00-17:00 and Friday 08:00-14:00. Those morning times must not create hot-meal planning slots. The mensa's **14:15-14:30 break** also remains unavailable.

## Sources and precedence

1. [Operator's regular location listing](https://www.studierendenwerk-aachen.de/de/gastronomie/mensen-und-cafeterien.html).
2. [Eupener's own weekly-menu page](https://www.studierendenwerk-aachen.de/de/Gastronomie/mensa-eupener-strasse-wochenplan.html), confirming its split service windows.
3. [Winter-semester announcement, 24 September 2026](https://www.studierendenwerk-aachen.de/de/aktuelles/beitrag/verlaengerte-oeffnungszeiten-im-wintersemester-kopie.html). Its linked timetable image was also inspected, confirming the table above for lecture time.
4. [Academica reopening, 6 October 2026](https://www.studierendenwerk-aachen.de/de/aktuelles/beitrag/die-mensa-academica-oeffnet-wieder-ihre-pforten.html), describing service from 12 October and temporary menu limitations.

Do not use the older 2023 Eupener announcement as evidence of uninterrupted 11:15-17:00 service: the current location page includes a break. Do not use a search-result snippet, an old third-party listing, or the operator's test subdomain as the authoritative timetable.

## Exceptions and freshness

- Date-specific closures/shortened opening take precedence over regular weekly windows. Check official notices and the selected location's page before enabling that date.
- Lecture-free periods and holidays need explicit dated schedules. A winter-lecture timetable is not valid indefinitely.
- A 25 September 2026 operator notice described Südpark as closed at that time. The later regular timetable alone is not proof of its live operational status; verify reopening/current notices before enabling it as an actual selectable location.
- Record the source URL, checked date, effective dates and service type with each schedule. The checked date above is research metadata, not an automated synchronization claim.
- Initially implement the verified primary location, Eupener Straße; enable additional locations only with date-appropriate operational verification.
- If current hours cannot be verified, show `Hours need verification` and do not offer unverified booking times. Historical/sample charts must remain explicitly labelled.

## Derived single-slot policy

The user may choose **exactly one** arrival-time bucket. Use a single radio group or single-select and validate one scalar slot ID on the server. Replacing a draft choice does not save a second plan.

Proposed granularity: **15-minute arrival buckets**, because it can represent opening at 11:15 and the 14:15 break precisely. This interval is a product-design choice, not an operator rule. A bucket is not a meal-duration promise or table reservation.

Generate slots inside one half-open service window, requiring `start >= opening` and `start + 15 minutes <= closing`, then exclude past times and dated exceptions.

For **Thursday at Eupener**:

- Lunch starts: 11:15, 11:30, ... 14:00; the last bucket ends at 14:15.
- Afternoon starts: 14:30, 14:45, ... 16:45; the last bucket ends at 17:00.
- No 14:15 start, no slot bridging the break, and no 17:00 start.

For **Friday at Eupener**, starts are 11:15, 11:30, ... 13:45. The last bucket ends at 14:00; 14:00 is not a selectable start.

Five bars in a design proposal are a preview, not the full day. The real interface must provide all eligible times and visible opening-hour context for the selected date.

## Verification checklist for implementation

- Thursday: include 11:15, 14:00, 14:30 and 16:45; exclude 11:00, 14:15 and 17:00.
- Friday: include 11:15 and 13:45; exclude 14:00 and afternoon service.
- Apply local dates/timezones, closure overrides and past-slot validation on the server.
- Reject multi-value input, including array-shaped submissions; do not merely hide multiple selection in the UI.
- Clearly distinguish regular hours, a confirmed dated exception, and unknown current status.
