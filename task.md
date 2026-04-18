# image upload endpoint

add endpoinnt to upload invoices as image

after upload 
save image to storage/app/invoices with unique name
save its info to db in invoice table
include:
- original file name
- file path
- mime type
- file size
- status (pending)
- uploaded at

uplaoad can be multiple:
each imge has sepreaet recored in invoice table

implemnt needed rooutes, controller request, model, migration
use repository pattern
use servic classess
must use api resource 
make validation
add view page for upload

