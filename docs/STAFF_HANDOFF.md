# Staff operational handoff

Prepared 6 November 2026. Admin staff ko verified HTTPS hospital URL aur individual account de. Staff login path `/staff/login` hai; shared passwords/accounts use na karein. Date/OPD timings Pakistan (`Asia/Karachi`) mein interpret hote hain. Admin ko final approved name, contacts, fees aur print footer confirm karna hai.

## Admin: opening, accounts aur day close

1. Staff accounts mein Reception aur Doctor users create/activate karein; each Doctor login ko correct doctor profile se link karein. Leaving staff ko deactivate karein. Password reset/role changes ke baad purana access revoke hota hai; affected staff dobara login kare.
2. Doctor fee, Priority surcharge, weekly OPD schedule, online visibility aur specific-date overrides verify karein. Doctor/day ki combined online + normal + Priority capacity 50 hai. Existing reservation count se neeche capacity/fees ko manually database mein edit na karein; UI validation follow karein. Current booked visits apna booked fee/hours retain karte hain.
3. Hospital settings mein public name, phone, address aur footer set karein. Currency production use se pehle decide karein; historical payments/refunds original currency retain karte hain. Settings save conflict ho to latest form reload karke values review karein.
4. Appointments, Patients/MR history, Finances aur Activity se review karein. Admin clinical prescriptions read/reprint kar sakta hai, Doctor ki jagah edit/complete nahi karta. Reception clinical access nahi rakhta.
5. Day close par correct hospital date, cashier aur currency filters ke saath collection, refunds aur net cash reconcile karein. Cancellation record cash refund ka proof nahi; actual refund separately check karein. Audit log append-only hai; records delete/change karke cash balance theek na karein.

## Reception: online patient arrival

1. Appointment reference/doctor/date find karein. Public reservation **Pending** hai; online booking khud MR, token ya cash payment nahi banati.
2. Patient identity verify karein. Name, age aur phone compare karein. Family members ka same phone ho sakta hai: correct existing MR explicitly select karein, wrong patient ko merge na karein. Naye patient ke liye registration route use karein; MR permanent hai.
3. Cash receive karke standard confirmation form ek baar submit karein. Server fee calculate karta hai. Committed receipt par MR, amount, doctor/date aur sequential token confirm karein. Existing confirmation dobara charge/collect na karein.
4. Receipt reprint existing payment se karein. Printer fail ho to same receipt reopen/reprint; nayi booking/payment na banayein.

## Reception: normal/Priority walk-in

Walk-in form par correct doctor/day aur verified returning MR/new identity choose karein. Priority sirf paid Reception option hai; server normal fee + configured surcharge calculate karta hai. Priority group queue mein pehle aata hai lekin current consultation interrupt nahi hoti. Priority ko online self-booking se apply nahi kiya ja sakta.

50 seats Full hon to UI ka refusal follow karein; extra token/cash record force na karein. Cancellation eligible seat release kar sakti hai; completed/no-show/past-date visits ko automatic reset se free nahi kiya jata.

## Doctor: queue aur prescription

1. Apne Doctor account mein correct day ki queue dekhein. **Next** current patient ko retain karta hai; Priority group pehle aur har group mein token order hota hai. Later Priority arrival current patient replace nahi karta.
2. Current patient ka MR/name confirm karein. Medicine, dose/frequency/duration, notes/advice enter karke draft save karein. Browser save result verify karein; network error par guide below follow karein.
3. Draft/print consultation complete nahi karta. Clinical review ke baad explicit **Complete OPD** action karein. Finalized prescription edit nahi hoti. Galti ho to Admin/authorized operational process ko report karein; database se final record rewrite na karein.
4. Prescription existing visit se reprint karein. Sirf apne scoped visits/records dekhein; Admin ko read-only clinical review milta hai. Completed visit ka seat/token/payment history retained rehta hai.

## Cancellation aur cash refund

Reception/Admin eligible visit par reason ke saath cancellation submit kar sakte hain; active current consultation/other ineligible states UI block karti hai. Exactly one cancellation seat release karti hai. Completed visit ko cancel karne ka workaround na banayein.

**Cancellation cash wapas nahi karti.** Admin original payment par remaining refundable balance review karke amount + reason ke saath refund separately record kare. Actual cash return ko refund receipt/reference se reconcile karein. Partial/full refunds original collected amount se zyada nahi ho sakte. Ambiguous response par duplicate refund/cash return na karein; same request recovery/result check karein.

## Session/network/printer failure

| Situation | Staff action |
| --- | --- |
| Submit par loading | Result aane dein; duplicate click/new tab se same cash operation na karein |
| Timeout / result uncertain | Original tab aur same payload/request retain karein; existing result/receipt check karke offered **same-request retry** use karein; cash dobara na lein/return karein |
| Session expired (419/401) | Offered separate sign-in tab mein **same staff account** se login; original page par session recheck, phir explicit retry |
| Permission revoked / other account | Operation stop; Admin se access resolve karein |
| Validation / Full | Inline reason review; server acceptance ke bina payment/token successful na samjhein |
| App unavailable (503) | Operator ko time + nonclinical reference dein; uncertain transaction dobara create na karein; recovery ke baad existing result verify karein |
| Printer/network failure after success | Existing receipt/prescription reopen aur reprint; financial/clinical action repeat na karein |

Receipt ke liye printer/browser ka actual **80 mm roll** paper, 100% scale aur headers/footers off use karein. Prescription **A4 portrait**, 100% scale aur headers/footers off se preview karein. Text clipping, medicine rows, MR/token/date, totals aur footer inspect karein; final margins real printer driver ke mutabiq staging mein approve hon. Physical print button explicit staff decision hai.

## Operator escalation aur acceptance

Hospital owner private contact sheet mein primary Admin, backup Admin, server operator, hosting account owner aur printer support contacts record kare. Passwords/APP_KEY recovery private password manager mein hon; is public guide mein patient info/passwords na daalein. Incident mein time, screen/action, safe reference aur displayed error collect karein; patient photo/clinical notes public GitHub issue mein na bhejein.

Operator [deployment runbook](DEPLOYMENT.md) se `/up`, `ops:check`, logs, backup age aur restore process check kare. Staff database reset/direct SQL repair na kare. No-show par online Pending aur paid unseen Confirmed reh sakte hain; date badalne se automatic cancellation/completion/refund nahi hota.

Go-live se pehle har role ke saath [deployment guide](DEPLOYMENT.md) ka staging verification flow execute karein, printer approve karein aur primary/backup owner ko guide ka private walkthrough dein. Guide prepared hai; actual staff training/signoff deployment ke baad record hoga.
