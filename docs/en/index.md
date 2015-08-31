The secure assets module can be used to apply read and write permissions to
the filesystem on a folder by folder basis.

Content authors sometimes need to load files into the CMS, and be assured that
these cannot be publicly accessed via deep linking. This is achieved by organising
these files into assigned folders, and using the secureassets module to apply
security restrictions to those folders.

## User documentation

 * [Securing file assets](userhelp/index.md)

## Considerations

It is important to take note when using secure files attached to DataObjects which other users may be able to edit. If that user does not have permission to view the file attached, then it will not appear
against that object, and modification may result in it being detached.

### Pages

Pages may be created in draft with secure files attached, but when this page is
published you will need to change the permissions on each file to make them accessible.

Try to avoid attaching secure images or other files to live pages (or other DataObjects)
which may be publicly viewed, to avoid unnecessary access denied errors appearing.

## Security of secure assets

A common use-case for this module is to provide security for a directory of files
uploaded by website visitors. This module will now by default enforce that all secure
assets be downloaded by a visitor's browser if they are allowed access to the file. This
is done with the 'Content-disposition' HTTP header.

An override is available to restore the previous behaviour of allowing files to be
loaded directly in the browser. To do so, set the
`SecureFileController.content_disposition` config variable to `inline`. Please review
and understand the security implications before doing so.

## Other Considerations

Securing files will cause extra load on your webserver and your database,
as the framework will check the database for access permissions, and pass the 
file data through the framework when it is output to the user.

It's also important to make sure that any .htaccess or web.config file
under assets is made writable by the webserver, as this module will
modify it as necessary to apply custom permissions.
