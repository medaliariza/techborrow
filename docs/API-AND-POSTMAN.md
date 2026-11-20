# TechBorrow HTTP endpoints and Postman guide

## Base URL and behavior

The app uses `/techborrow` on local XAMPP and the domain root on the configured public host. Set the collection variable `base_url` to `http://localhost/techborrow` locally or `https://techborrow.freedev.app` for the public site.

These are PHP page/form routes, not JSON REST endpoints. Successful form posts typically return a redirect (302) to a page which renders a flash message. Postman may follow that redirect and show the final HTML (200). A successful response is not a JSON body. PHP session cookies are used for authenticated pages; use the same Postman cookie jar and sign in before protected requests. Role checks redirect unauthorized users rather than returning a JSON 401/403.

## Endpoint inventory

| Method | Path | Access | Purpose / important fields |
|---|---|---|---|
| GET | `/index.php` | Public | Sign-in form |
| POST | `/index.php` | Public | Sign in: `email`, `password`; active account required |
| POST | `/register.php` | Public | Register instructor account; inspect the page for required form fields |
| GET | `/student-borrow.php` | Public | Public student equipment request form |
| POST | `/actions/submit-student-request.php` | Public | Student request multipart form: `full_name`, `student_number`, `email`, `department`, `purpose`, `location_room`, `start_datetime`, `due_datetime`, `equipment_id[]`, `quantity[]`, `photo_consent=1`, and `student_photo` file |
| POST | `/actions/submit-request.php` | Instructor | Authenticated instructor request: `purpose`, `location_room`, `start_datetime`, `due_datetime`, matching arrays `equipment_id[]` and `quantity[]` |
| POST | `/actions/review-request.php` | Admin | Review pending request: `request_id`, `decision=approve|reject`, optional `reason` |
| POST | `/actions/release-item.php` | Admin | Release approved request: `request_id` |
| POST | `/actions/process-return.php` | Admin | Inspect return: `transaction_id`, `return_datetime`, `condition=good|damaged|incomplete|maintenance`, optional `remarks` |
| POST | `/actions/manage-equipment.php` | Admin | Create/update/delete equipment; `action`, `equipment_id` as applicable, and equipment form fields |
| POST | `/actions/manage-category.php` | Admin | Create category: `name`, optional `description` |
| POST | `/actions/manage-maintenance.php` | Admin | Create/update maintenance item: `action`, `equipment_id`, `issue_description`, `status`, optional `maintenance_id`, `assigned_to` |
| POST | `/actions/create-announcement.php` | Admin | Create/update/delete: `action`, `title`, `message`, and `announcement_id` as applicable |
| POST | `/actions/update-user-status.php` | Admin | Change account status: `user_id`, `account_status=active|inactive|suspended` |
| POST | `/actions/mark-notification-read.php` | Signed-in user | Mark notification read: `notification_id` |
| POST | `/actions/update-profile.php` | Instructor | Update profile: `full_name`, `email`, `department`, optional `new_password`, `confirm_password` |

Browse the associated pages for more GET/filter/report functions: `/instructor/equipment.php`, `/instructor/my-requests.php`, `/instructor/my-borrowed-items.php`, `/admin/requests.php`, `/admin/release-equipment.php`, `/admin/returns.php`, `/admin/reports.php`, `/admin/users.php`, `/admin/maintenance.php`, `/admin/categories.php`, `/admin/equipment.php`, and `/admin/announcements.php`.

## Import and run

1. In Postman choose **Import** and select `TechBorrow.postman_collection.json`.
2. Set collection variable `base_url` to the app root, e.g. `http://localhost/techborrow`.
3. Run **Sign in (form)** with one of the provisioned test accounts. Postman should retain the `PHPSESSID` cookie.
4. Run requests appropriate to that account's role. Use valid existing equipment IDs, request IDs, transaction IDs, and dates from the running app.
5. For **Submit public student request**, set the `student_photo` form-data row to a real JPEG/PNG/WebP under 5 MB; provide consent as `1`.
6. Observe the redirect/HTML and the resulting page's flash message. Verify state changes in the UI/database; there is no JSON response contract.

The sample requests are state-changing. Use a disposable test database and create prerequisite equipment/request/transaction records before running approval, release, or return actions.
