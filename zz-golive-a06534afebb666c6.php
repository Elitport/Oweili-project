<?php
/* TEMPORARY (owner, 28 Nov 2026): go-live helper for the invoices, identity checks, preview pages, VAT and the artwork commission.
   Locked by a secret key; removed straight after.
   ?a=check (read only) · ?a=files (read only: live files with git fingerprints) · ?a=backup (full database + files backup into .backups)
   ?a=run (a fresh full backup, then only the add-only database files + the artwork commission 20%, once)
   ?a=idkey (makes the identity key file if missing; shows only where it is, never the key) · ?a=errors (the newest error log lines)
   ?a=restore&f=db-...sql.gz (puts the database back from that backup) */
if (!hash_equals('d78abfb425e6ae02367385ba623c44fadfacbcc1c668df99488d8747c13f1eeb', hash('sha256', (string) ($_GET['k'] ?? '')))) { http_response_code(404); exit; }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/platform_lib.php';
header('Content-Type: text/plain; charset=utf-8'); header('Cache-Control: no-store'); header('X-Robots-Tag: noindex');
@set_time_limit(900); @ini_set('memory_limit', '768M');
$db = getDB();
$a = (string) ($_GET['a'] ?? 'check');
$root = __DIR__;
$SQL = ['invoices_update.sql' => 'LS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09Ci0tICBPd2VpbGkg4oCUIGludm9pY2VzIGZvciBldmVyeSBwdXJjaGFzZSAob3duZXIsIDI3IE5vdiAyMDI2KQotLSAgT25seSBhZGRzLiBTYWZlIHRvIHJ1biBtb3JlIHRoYW4gb25jZS4KLS0KLS0gIGludm9pY2VzICAgICAgICAgICAgb25lIHJvdyBwZXIgcHVyY2hhc2UgKGFydHdvcmsgLyBnaWZ0IC8gY29tbWlzc2lvbgotLSAgICAgICAgICAgICAgICAgICAgICBvcmRlciwgY291cnNlIG9yIHdvcmtzaG9wIGJvb2tpbmcsIGZyZWUKLS0gICAgICAgICAgICAgICAgICAgICAgcmVnaXN0cmF0aW9uLCBtdXNldW0gdGlja2V0KS4gVGhlIGRldGFpbHMgYXJlCi0tICAgICAgICAgICAgICAgICAgICAgIGZyb3plbiB3aGVuIGl0IGlzIG1hZGUsIHNvIHRoZSBQREYgbmV2ZXIgY2hhbmdlcwotLSAgaW52b2ljZV9jb3VudGVycyAgICB0aGUgbGFzdCBudW1iZXIgdXNlZCBlYWNoIHllYXIgKE9XLTIwMjYtMDAwMDEpCi0tID09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PQoKQ1JFQVRFIFRBQkxFIElGIE5PVCBFWElTVFMgYGludm9pY2VzYCAoCiAgYGlkYCAgICAgICAgICAgICAgQklHSU5UIFVOU0lHTkVEIE5PVCBOVUxMIEFVVE9fSU5DUkVNRU5ULAogIGBpbnZvaWNlX25vYCAgICAgIFZBUkNIQVIoMjApICAgICBOT1QgTlVMTCwKICBgeWVhcmAgICAgICAgICAgICBTTUFMTElOVCBVTlNJR05FRCBOT1QgTlVMTCwKICBgc2VxYCAgICAgICAgICAgICBJTlQgVU5TSUdORUQgICAgTk9UIE5VTEwsCiAgYHNvdXJjZV9raW5kYCAgICAgVkFSQ0hBUigxMikgICAgIE5PVCBOVUxMLCAgICAgICAgICAgLS0gYXJ0d29yayAvIGdpZnQgLyBjb21taXNzaW9uIC8gY291cnNlIC8gd29ya3Nob3AgLyB0aWNrZXQKICBgc291cmNlX2lkYCAgICAgICBCSUdJTlQgVU5TSUdORUQgTk9UIE5VTEwsCiAgYHJlZl9ub2AgICAgICAgICAgVkFSQ0hBUig0MCkgICAgIE5PVCBOVUxMIERFRkFVTFQgJycsIC0tIHRoZSBvcmRlciBvciBib29raW5nIG51bWJlcgogIGB1c2VyX2lkYCAgICAgICAgIEJJR0lOVCBVTlNJR05FRCBOVUxMIERFRkFVTFQgTlVMTCwgIC0tIHRoZSBjdXN0b21lcidzIGFjY291bnQsIHdoZW4gdGhlcmUgaXMgb25lCiAgYGN1c3RvbWVyX25hbWVgICAgVkFSQ0hBUigxNTApICAgIE5PVCBOVUxMIERFRkFVTFQgJycsCiAgYGN1c3RvbWVyX2VtYWlsYCAgVkFSQ0hBUigxOTApICAgIE5PVCBOVUxMIERFRkFVTFQgJycsCiAgYGl0ZW1fZW5gICAgICAgICAgVkFSQ0hBUigyNTUpICAgIE5PVCBOVUxMIERFRkFVTFQgJycsCiAgYGl0ZW1fYXJgICAgICAgICAgVkFSQ0hBUigyNTUpICAgIE5PVCBOVUxMIERFRkFVTFQgJycsCiAgYGFtb3VudGAgICAgICAgICAgREVDSU1BTCgxMCwyKSAgIE5PVCBOVUxMIERFRkFVTFQgMC4wMCwgLS0gd2hhdCB0aGUgY3VzdG9tZXIgcGFpZCAoVkFUIGluY2x1ZGVkIHdoZW4gaXQgaXMgYSB0YXggaW52b2ljZSkKICBgaXNfdGF4YCAgICAgICAgICBUSU5ZSU5UKDEpICAgICAgTk9UIE5VTEwgREVGQVVMVCAwLCAgICAtLSAwID0gcHVyY2hhc2UgcmVjZWlwdCwgMSA9IHRheCBpbnZvaWNlCiAgYHZhdF9yYXRlYCAgICAgICAgREVDSU1BTCg1LDIpICAgIE5PVCBOVUxMIERFRkFVTFQgMC4wMCwKICBgdmF0X2Ftb3VudGAgICAgICBERUNJTUFMKDEwLDIpICAgTk9UIE5VTEwgREVGQVVMVCAwLjAwLAogIGB2YXRfbnVtYmVyYCAgICAgIFZBUkNIQVIoMjApICAgICBOT1QgTlVMTCBERUZBVUxUICcnLAogIGBzZWxsZXJfYWRkcmVzc2AgIFZBUkNIQVIoNDAwKSAgICBOT1QgTlVMTCBERUZBVUxUICcnLCAgIC0tIE93bmVyIHNldHRpbmdzICJJbnZvaWNlIGFkZHJlc3MiLCBmcm96ZW47IHByaW50ZWQgb25seSBpbiB0aGUgUERGCiAgYGN1cnJlbmN5YCAgICAgICAgQ0hBUigzKSAgICAgICAgIE5PVCBOVUxMIERFRkFVTFQgJ1NBUicsCiAgYGxhbmdgICAgICAgICAgICAgQ0hBUigyKSAgICAgICAgIE5PVCBOVUxMIERFRkFVTFQgJ2VuJywKICBgaXNzdWVkX2F0YCAgICAgICBEQVRFVElNRSAgICAgICAgTk9UIE5VTEwsCiAgYGVtYWlsZWRfYXRgICAgICAgREFURVRJTUUgICAgICAgIE5VTEwgREVGQVVMVCBOVUxMLAogIFBSSU1BUlkgS0VZIChgaWRgKSwKICBVTklRVUUgS0VZIGB1cV9pbnZfbm9gIChgaW52b2ljZV9ub2ApLAogIFVOSVFVRSBLRVkgYHVxX2ludl95ZWFyX3NlcWAgKGB5ZWFyYCwgYHNlcWApLAogIFVOSVFVRSBLRVkgYHVxX2ludl9zb3VyY2VgIChgc291cmNlX2tpbmRgLCBgc291cmNlX2lkYCksCiAgS0VZIGBpZHhfaW52X3VzZXJgIChgdXNlcl9pZGApLAogIEtFWSBgaWR4X2ludl9lbWFpbGAgKGBjdXN0b21lcl9lbWFpbGApLAogIEtFWSBgaWR4X2ludl9pc3N1ZWRgIChgaXNzdWVkX2F0YCkKKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpOwoKQ1JFQVRFIFRBQkxFIElGIE5PVCBFWElTVFMgYGludm9pY2VfY291bnRlcnNgICgKICBgeWVhcmAgIFNNQUxMSU5UIFVOU0lHTkVEIE5PVCBOVUxMLAogIGBsYXN0YCAgSU5UIFVOU0lHTkVEICAgICAgTk9UIE5VTEwgREVGQVVMVCAwLAogIFBSSU1BUlkgS0VZIChgeWVhcmApCikgRU5HSU5FPUlubm9EQiBERUZBVUxUIENIQVJTRVQ9dXRmOG1iNCBDT0xMQVRFPXV0ZjhtYjRfdW5pY29kZV9jaTsKCi0tIDI4IE5vdiAyMDI2IChvd25lcik6IHRoZSBpbnZvaWNlIGFkZHJlc3MsIGZvciBhIHRhYmxlIG1hZGUgYmVmb3JlIHRoaXMgY29sdW1uIGV4aXN0ZWQKQUxURVIgVEFCTEUgYGludm9pY2VzYCBBREQgQ09MVU1OIElGIE5PVCBFWElTVFMgYHNlbGxlcl9hZGRyZXNzYCBWQVJDSEFSKDQwMCkgTk9UIE5VTEwgREVGQVVMVCAnJyBBRlRFUiBgdmF0X251bWJlcmA7Cg==', 'identity_update.sql' => 'LS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09Ci0tICBPd2VpbGkg4oCUIGlkZW50aXR5IGNoZWNrcyBhdCBzaWduLXVwIChvd25lciwgMjcgTm92IDIwMjYpCi0tICBPbmx5IGFkZHMuIFNhZmUgdG8gcnVuIG1vcmUgdGhhbiBvbmNlLgotLQotLSAgaWRlbnRpdHlfY2hlY2tzICAgb25lIHJvdyBwZXIgYXJ0aXN0IG9yIGluc3RpdHV0ZSBhY2NvdW50OiB0aGUgaWRlbnRpdHkKLS0gICAgICAgICAgICAgICAgICAgIGRvY3VtZW50IChhcnRpc3RzOiBTYXVkaSBuYXRpb25hbCBJRCwgSXFhbWEgb3IgcGFzc3BvcnQsCi0tICAgICAgICAgICAgICAgICAgICB3aXRoIHR5cGUsIG51bWJlciwgZXhwaXJ5IGFuZCBhIHBob3RvOyBpbnN0aXR1dGVzOiB0aGUKLS0gICAgICAgICAgICAgICAgICAgIFNhdWRpIE5hdGlvbmFsIEFkZHJlc3MgY2VydGlmaWNhdGUpLCB0aGUgcmV2aWV3IGxpbmssCi0tICAgICAgICAgICAgICAgICAgICB0aGUgY29uc2VudCB0aW1lIGFuZCB0aGUgb3duZXIncyBkZWNpc2lvbi4KLS0gICAgICAgICAgICAgICAgICAgIFRoZSBkb2N1bWVudCBudW1iZXIgaXMgRU5DUllQVEVEIChBRVMtMjU2LUdDTSk7IHRoZQotLSAgICAgICAgICAgICAgICAgICAgcGhvdG9zIGFyZSBlbmNyeXB0ZWQgZmlsZXMgb3V0c2lkZSB0aGUgcHVibGljIGZvbGRlci4KLS0gICAgICAgICAgICAgICAgICAgIEtlcHQgYWZ0ZXIgYXBwcm92YWwgYW5kIGFmdGVyIHRoZSBhY2NvdW50IGNsb3NlcyBmb3IKLS0gICAgICAgICAgICAgICAgICAgIHRoZSB5ZWFycyBzZXQgaW4gT3duZXIgc2V0dGluZ3MgKGRlZmF1bHQgNSksIHRoZW4gdGhlCi0tICAgICAgICAgICAgICAgICAgICBmaWxlcyBhbmQgdGhlIG51bWJlciBhcmUgZGVsZXRlZCBhdXRvbWF0aWNhbGx5LgotLSAgICAgICAgICAgICAgICAgICAgVGhlIG5hbWUgYW5kIGVtYWlsIGFyZSBjb3BpZWQgaGVyZSwgc28gdGhlIHJlY29yZCBzdGlsbAotLSAgICAgICAgICAgICAgICAgICAgc2F5cyB3aG9zZSBpdCB3YXMgYWZ0ZXIgdGhlIGFjY291bnQgaXMgZGVsZXRlZC4KLS0gIGlkZW50aXR5X3Bob3RvcyAgIGluc3RpdHV0ZXM6IDIgdG8gNSBwaG90b3Mgb2YgdGhlIHByZW1pc2VzIG9yIG9mCi0tICAgICAgICAgICAgICAgICAgICBzdHVkZW50cycgd29yayAoZW5jcnlwdGVkIGZpbGVzLCBwcml2YXRlKQotLSA9PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT0KCkNSRUFURSBUQUJMRSBJRiBOT1QgRVhJU1RTIGBpZGVudGl0eV9jaGVja3NgICgKICBgaWRgICAgICAgICAgICAgIEJJR0lOVCBVTlNJR05FRCBOT1QgTlVMTCBBVVRPX0lOQ1JFTUVOVCwKICBgdXNlcl9pZGAgICAgICAgIEJJR0lOVCBVTlNJR05FRCBOVUxMIERFRkFVTFQgTlVMTCwKICBgcm9sZWAgICAgICAgICAgIFZBUkNIQVIoMTIpICAgICBOT1QgTlVMTCBERUZBVUxUICdhcnRpc3QnLCAgIC0tIGFydGlzdCAvIGluc3RpdHV0ZQogIGBwZXJzb25fbmFtZWAgICAgVkFSQ0hBUigxNTApICAgIE5PVCBOVUxMIERFRkFVTFQgJycsCiAgYHBlcnNvbl9lbWFpbGAgICBWQVJDSEFSKDE5MCkgICAgTk9UIE5VTEwgREVGQVVMVCAnJywKICBgaWRfdHlwZWAgICAgICAgIFZBUkNIQVIoMTIpICAgICBOT1QgTlVMTCBERUZBVUxUICcnLCAgICAgICAgIC0tIG5hdGlvbmFsX2lkIC8gaXFhbWEgLyBwYXNzcG9ydCAoYXJ0aXN0cykKICBgaWRfbnVtYmVyX2VuY2AgIFRFWFQgICAgICAgICAgICBOVUxMLCAgICAgICAgICAgICAgICAgICAgICAgICAtLSBlbmNyeXB0ZWQsIG5ldmVyIHNob3duIGluIGEgbGlzdAogIGBpZF9sYXN0NGAgICAgICAgVkFSQ0hBUig0KSAgICAgIE5PVCBOVUxMIERFRkFVTFQgJycsCiAgYGlkX2V4cGlyeWAgICAgICBEQVRFICAgICAgICAgICAgTlVMTCBERUZBVUxUIE5VTEwsCiAgYGlkX2ZpbGVgICAgICAgICBWQVJDSEFSKDgwKSAgICAgTk9UIE5VTEwgREVGQVVMVCAnJywgICAgICAgICAgLS0gZW5jcnlwdGVkIHBob3RvIG9mIHRoZSBkb2N1bWVudCAoYXJ0aXN0cykKICBgYWRkcmVzc19maWxlYCAgIFZBUkNIQVIoODApICAgICBOT1QgTlVMTCBERUZBVUxUICcnLCAgICAgICAgICAtLSBlbmNyeXB0ZWQgTmF0aW9uYWwgQWRkcmVzcyBjZXJ0aWZpY2F0ZSAoaW5zdGl0dXRlcykKICBgcmV2aWV3X2xpbmtgICAgIFZBUkNIQVIoMjU1KSAgICBOT1QgTlVMTCBERUZBVUxUICcnLCAgICAgICAgICAtLSBJbnN0YWdyYW0gb3Igd2Vic2l0ZSwgZm9yIHRoZSBvd25lcidzIHJldmlldyBvbmx5CiAgYGNvbnNlbnRfYXRgICAgICBEQVRFVElNRSAgICAgICAgTlVMTCBERUZBVUxUIE5VTEwsICAgICAgICAgICAgLS0gdGhlIHJlcXVpcmVkIHRpY2sgYXQgc2lnbi11cAogIGBzdGF0dXNgICAgICAgICAgVkFSQ0hBUigxMCkgICAgIE5PVCBOVUxMIERFRkFVTFQgJ3BlbmRpbmcnLCAgIC0tIHBlbmRpbmcgLyBhcHByb3ZlZCAvIHJlZnVzZWQKICBgcmVhc29uYCAgICAgICAgIFZBUkNIQVIoNTAwKSAgICBOT1QgTlVMTCBERUZBVUxUICcnLAogIGBkZWNpZGVkX2F0YCAgICAgREFURVRJTUUgICAgICAgIE5VTEwgREVGQVVMVCBOVUxMLAogIGBjbG9zZWRfYXRgICAgICAgREFURVRJTUUgICAgICAgIE5VTEwgREVGQVVMVCBOVUxMLCAgICAgICAgICAgIC0tIHRoZSBhY2NvdW50IHdhcyBjbG9zZWQgb3IgcmVmdXNlZDogdGhlIGtlZXBpbmcgY2xvY2sgc3RhcnRzCiAgYHB1cmdlZF9hdGAgICAgICBEQVRFVElNRSAgICAgICAgTlVMTCBERUZBVUxUIE5VTEwsICAgICAgICAgICAgLS0gdGhlIGZpbGVzIGFuZCB0aGUgbnVtYmVyIHdlcmUgZGVsZXRlZAogIGBjcmVhdGVkX2F0YCAgICAgVElNRVNUQU1QICAgICAgIE5PVCBOVUxMIERFRkFVTFQgQ1VSUkVOVF9USU1FU1RBTVAsCiAgUFJJTUFSWSBLRVkgKGBpZGApLAogIFVOSVFVRSBLRVkgYHVxX2lkY191c2VyYCAoYHVzZXJfaWRgKSwKICBLRVkgYGlkeF9pZGNfc3RhdHVzYCAoYHN0YXR1c2ApLAogIEtFWSBgaWR4X2lkY19jbG9zZWRgIChgY2xvc2VkX2F0YCkKKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpOwoKQ1JFQVRFIFRBQkxFIElGIE5PVCBFWElTVFMgYGlkZW50aXR5X3Bob3Rvc2AgKAogIGBpZGAgICAgICAgICBCSUdJTlQgVU5TSUdORUQgTk9UIE5VTEwgQVVUT19JTkNSRU1FTlQsCiAgYGNoZWNrX2lkYCAgIEJJR0lOVCBVTlNJR05FRCBOT1QgTlVMTCwKICBgZmlsZV9uYW1lYCAgVkFSQ0hBUig4MCkgICAgIE5PVCBOVUxMLAogIGBjcmVhdGVkX2F0YCBUSU1FU1RBTVAgICAgICAgTk9UIE5VTEwgREVGQVVMVCBDVVJSRU5UX1RJTUVTVEFNUCwKICBQUklNQVJZIEtFWSAoYGlkYCksCiAgS0VZIGBpZHhfaWRwX2NoZWNrYCAoYGNoZWNrX2lkYCkKKSBFTkdJTkU9SW5ub0RCIERFRkFVTFQgQ0hBUlNFVD11dGY4bWI0IENPTExBVEU9dXRmOG1iNF91bmljb2RlX2NpOwo=', 'vat_settings.sql' => 'LS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09Ci0tICB2YXRfc2V0dGluZ3Muc3FsIOKAlCBWQVQgbnVtYmVyIGFuZCBWQVQgc3RhcnQgZGF0ZSAob3duZXIsIDI4IE5vdiAyMDI2KQotLSAgU2V0cyB0aGUgVkFUIG51bWJlciAzMTQ1MjU3Njc0MDAwMDMgYW5kIHRoZSBzdGFydCBkYXRlIDIwMjYtMTItMDEuCi0tICBUaGUgVkFUIHN3aXRjaCBpcyBOT1QgdHVybmVkIG9uOiBpdCBrZWVwcyBpdHMgY3VycmVudCB2YWx1ZSAob2ZmIHdoZW4KLS0gIHRoZXJlIGlzIG5vIFZBVCByb3cgeWV0KS4gVGhlIG93bmVyIHR1cm5zIGl0IG9uIGluIE93bmVyIHNldHRpbmdzIOKGkiBWQVQuCi0tICBTYWZlIHRvIHJ1biBtb3JlIHRoYW4gb25jZS4gQ2hhbmdlcyBvbmx5IHRoZSAndmF0JyBzZXR0aW5nIHJvdy4KLS0gPT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09PT09CklOU0VSVCBJTlRPIHNpdGVfc2V0dGluZ3MgKHNldHRpbmdfa2V5LCBzZXR0aW5nX3ZhbHVlKQpWQUxVRVMgKCd2YXQnLCAneyJvbiI6ZmFsc2UsInJhdGUiOjE1LCJudW1iZXIiOiIzMTQ1MjU3Njc0MDAwMDMiLCJzdGFydCI6IjIwMjYtMTItMDEifScpCk9OIERVUExJQ0FURSBLRVkgVVBEQVRFIHNldHRpbmdfdmFsdWUgPSBKU09OX1NFVCgKICAgIElGKEpTT05fVkFMSUQoc2V0dGluZ192YWx1ZSksIHNldHRpbmdfdmFsdWUsICd7Im9uIjpmYWxzZSwicmF0ZSI6MTV9JyksCiAgICAnJC5udW1iZXInLCAnMzE0NTI1NzY3NDAwMDAzJywKICAgICckLnN0YXJ0JywgJzIwMjYtMTItMDEnKTsK'];
$walk = function (array $skipTop) use ($root): array {
    $out = []; $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) { if (!$f->isFile()) continue; $rel = substr($f->getPathname(), strlen($root) + 1);
        $top = explode('/', $rel)[0]; if (in_array($top, $skipTop, true)) continue; $out[] = $rel; }
    sort($out); return $out; };
$setting = function (string $k) use ($db) { $s = $db->prepare('SELECT setting_value FROM site_settings WHERE setting_key = ?'); $s->execute([$k]); $v = $s->fetchColumn(); return $v === false ? '(not set)' : (string) $v; };
$show = function () use ($db, $setting) {
    foreach (['users', 'artworks', 'activity_log', 'institutes', 'courses', 'course_registrations', 'orders', 'certificates', 'art_places', 'articles', 'workshop_bookings', 'museum_tickets'] as $t) {
        try { echo $t, ': ', $db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(), "\n"; } catch (Throwable $e) { echo $t, ': missing', "\n"; } }
    foreach (['invoices', 'invoice_counters', 'identity_checks', 'identity_photos'] as $t) echo 'table ', $t, ': ', $db->query("SHOW TABLES LIKE " . $db->quote($t))->fetchColumn() ? 'yes' : 'no', "\n";
    try { echo 'invoices.seller_address: ', $db->query("SHOW COLUMNS FROM invoices LIKE 'seller_address'")->fetchColumn() ? 'yes' : 'no', "\n"; } catch (Throwable $e) {}
    foreach (['vat', 'commission_rate', 'commission_history', 'course_commission', 'invoice_address', 'courses_on', 'payments_enabled', 'demo_content'] as $k) echo 'setting ', $k, ': ', $setting($k), "\n";
    try { $db->exec('CREATE TEMPORARY TABLE zz_tmp_check (id INT)'); $db->exec('DROP TEMPORARY TABLE zz_tmp_check'); echo "temporary tables (Preview pages): allowed\n"; } catch (Throwable $e) { echo "temporary tables (Preview pages): NOT allowed: ", $e->getMessage(), "\n"; }
    $above = dirname(__DIR__) . '/oweili-private/'; echo 'folder above public_html for private files: ', is_dir($above) ? 'exists' : (is_writable(dirname(__DIR__)) ? 'can be made' : 'cannot be made (the blocked .private folder will be used)'), "\n";
};
$backup = function () use ($db, $root, $walk): bool {
    $dir = $root . '/.backups'; if (!is_dir($dir)) @mkdir($dir, 0700, true);
    if (!is_file($dir . '/.htaccess')) @file_put_contents($dir . '/.htaccess', "Require all denied\n");
    $stamp = gmdate('Ymd-His'); $dbName = preg_replace('/[^a-z0-9_]/i', '', (string) $db->query('SELECT DATABASE()')->fetchColumn());
    $file = "$dir/db-$dbName-$stamp.sql.gz"; $gz = gzopen($file, 'wb6'); if (!$gz) { echo "database backup FAILED\n"; return false; }
    gzwrite($gz, "-- Oweili full backup " . gmdate('c') . "\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");
    $tables = $db->query('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"')->fetchAll(PDO::FETCH_COLUMN); $rows = 0;
    foreach ($tables as $t) { $t = str_replace('`', '', (string) $t); $c = $db->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_NUM);
        gzwrite($gz, "\nDROP TABLE IF EXISTS `$t`;\n" . $c[1] . ";\n"); $st = $db->query("SELECT * FROM `$t`");
        while ($r = $st->fetch(PDO::FETCH_NUM)) { gzwrite($gz, "INSERT INTO `$t` VALUES (" . implode(',', array_map(fn($v) => $v === null ? 'NULL' : $db->quote((string) $v), $r)) . ");\n"); $rows++; } }
    gzwrite($gz, "\nSET FOREIGN_KEY_CHECKS=1;\n"); gzclose($gz);
    if (filesize($file) < 500) { echo "database backup FAILED (too small)\n"; return false; }
    echo "database backup ok: .backups/", basename($file), " (", count($tables), " tables, $rows rows, ", filesize($file), " bytes)\n";
    /* files: everything except the backups folder itself and the picture copies (they are made again by themselves) */
    $tar = "$dir/files-$stamp.tar.gz"; $g = gzopen($tar, 'wb6'); if (!$g) { echo "files backup FAILED\n"; return false; }
    $n = 0; $bytes = 0;
    foreach ($walk(['.backups']) as $rel) {
        if (strpos($rel, 'uploads/thumbs/') === 0) continue;
        $path = "$root/$rel"; $size = (int) @filesize($path); $fh = @fopen($path, 'rb'); if (!$fh) { echo "cannot read $rel\n"; continue; }
        $name = $rel; $prefix = '';
        if (strlen($name) > 100) { $cut = strrpos(substr($name, 0, 155), '/'); if ($cut === false) { echo "name too long, skipped: $rel\n"; fclose($fh); continue; } $prefix = substr($name, 0, $cut); $name = substr($name, $cut + 1); }
        $h = str_pad($name, 100, "\0") . str_pad(sprintf('%07o', 0644), 8, "\0") . str_pad('0000000', 8, "\0") . str_pad('0000000', 8, "\0")
           . str_pad(sprintf('%011o', $size), 12, "\0") . str_pad(sprintf('%011o', (int) @filemtime($path)), 12, "\0") . '        ' . '0' . str_repeat("\0", 100)
           . "ustar\0" . '00' . str_repeat("\0", 32) . str_repeat("\0", 32) . str_repeat("\0", 8) . str_repeat("\0", 8) . str_pad($prefix, 155, "\0") . str_repeat("\0", 12);
        $sum = 0; for ($i = 0; $i < 512; $i++) $sum += ord($h[$i]);
        $h = substr_replace($h, str_pad(sprintf('%06o', $sum), 6, '0', STR_PAD_LEFT) . "\0 ", 148, 8);
        gzwrite($g, $h); while (!feof($fh)) { $buf = fread($fh, 1 << 20); if ($buf === '' || $buf === false) break; gzwrite($g, $buf); }
        fclose($fh); if ($size % 512) gzwrite($g, str_repeat("\0", 512 - $size % 512)); $n++; $bytes += $size;
    }
    gzwrite($g, str_repeat("\0", 1024)); gzclose($g);
    echo "files backup ok: .backups/", basename($tar), " ($n files, $bytes bytes, ", filesize($tar), " bytes packed)\n";
    return $n > 50;
};
if ($a === 'check') { $show(); exit; }
if ($a === 'files') {
    foreach ($walk(['.backups', 'uploads', '.logs', '.private', '.git']) as $rel) {
        if ($rel === 'config.php') continue; $c = (string) @file_get_contents("$root/$rel");
        echo sha1('blob ' . strlen($c) . "\0" . $c), ' ', $rel, "\n"; }
    exit; }
if ($a === 'backup') { $show(); $backup(); exit; }
if ($a === 'errors') { $f = $root . '/.logs/php-errors.log'; if (!is_file($f)) { echo "no error log\n"; exit; } $l = file($f); echo 'lines: ', count($l), "\n"; foreach (array_slice($l, -40) as $x) echo $x; exit; }
if ($a === 'idkey') {
    if (!is_file($root . '/identity_lib.php')) { echo "identity_lib.php not published yet\n"; exit; }
    require_once $root . '/identity_lib.php';
    if (defined('OWEILI_ID_KEY') && preg_match('/^[a-f0-9]{64}$/i', (string) OWEILI_ID_KEY)) { echo "the key is in config.php (OWEILI_ID_KEY); no file is used\n"; exit; }
    try { owIdKey(); } catch (Throwable $e) { echo "FAILED: ", $e->getMessage(), "\n"; exit; }
    $f = owIdDir() . 'id.key'; $rel = strpos($f, dirname(__DIR__)) === 0 ? substr($f, strlen(dirname(__DIR__)) + 1) : $f;
    echo 'key file: ', $rel, "\n", 'outside public_html: ', strpos($f, $root . '/') === 0 ? 'no' : 'yes', "\n", 'size: ', filesize($f), " bytes\n", 'permissions: ', substr(sprintf('%o', fileperms($f)), -4), "\n";
    exit; }
if ($a === 'restore') {
    $f = basename((string) ($_GET['f'] ?? '')); $p = $root . '/.backups/' . $f;
    if (!preg_match('/^db-[A-Za-z0-9_]+-\d{8}-\d{6}\.sql\.gz$/', $f) || !is_file($p)) { echo "no such backup\n"; exit; }
    $sql = (string) gzdecode((string) file_get_contents($p)); if (strlen($sql) < 500) { echo "backup unreadable\n"; exit; }
    $n = 0; $buf = ''; $q = false; $len = strlen($sql);
    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];
        if ($q) { $buf .= $c; if ($c === '\\') { $buf .= $sql[++$i] ?? ''; continue; } if ($c === "'") $q = false; continue; }
        if ($c === '-' && substr($sql, $i, 3) === '-- ' && ($i === 0 || $sql[$i - 1] === "\n")) { $e = strpos($sql, "\n", $i); $i = $e === false ? $len : $e; continue; }
        $buf .= $c;
        if ($c === "'") { $q = true; continue; }
        if ($c === ';') { $s = trim($buf); $buf = ''; if ($s !== ';' && $s !== '') { $db->exec($s); $n++; } }
    }
    echo "restored: $n statements from $f\n"; $show(); exit; }
if ($a !== 'run') exit;
if (is_file($root . '/.logs/zz-golive-vat-done')) { echo "already run\n"; $show(); exit; }
$show();
if (!$backup()) { echo "STOPPED: no backup, nothing changed\n"; exit; }
$n = 0;
foreach ($SQL as $name => $b64) {
    $text = base64_decode($b64); $text = preg_replace('/^\s*--.*$/m', '', $text); $text = preg_replace('/\s--\s.*$/m', '', $text);
    foreach (array_filter(array_map('trim', explode(';', $text))) as $st) {
        if (!preg_match('/^(CREATE TABLE IF NOT EXISTS|ALTER TABLE `invoices` ADD COLUMN IF NOT EXISTS|INSERT INTO site_settings)/i', $st)) { echo "STOPPED: unexpected line in $name: ", substr($st, 0, 80), "\n"; exit; }
        $db->exec($st); $n++; } }
echo "database lines run: $n\n";
$vat = json_decode($setting('vat'), true);
if (is_array($vat) && !empty($vat['on'])) { $vat['on'] = false; $db->prepare("UPDATE site_settings SET setting_value = ? WHERE setting_key = 'vat'")->execute([json_encode($vat)]); echo "VAT switch was on: switched OFF\n"; }
$GLOBALS['OW_SETTINGS_CACHE'] = [];
echo 'artwork commission 20%: ', owSetCommission(20.0) ? 'set' : 'FAILED', "\n";
owLog('golive', null, null, 'invoices, identity, VAT settings (switch off), artwork commission 20%', ['type' => 'admin', 'id' => null]);
@file_put_contents($root . '/.logs/zz-golive-vat-done', gmdate('c'));
echo "after:\n"; $show();
