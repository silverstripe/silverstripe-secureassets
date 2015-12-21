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

Securing files will cause extra load on your webserver and your database,
as the framework will check the datatabase for access permissions, and pass the
file data through the framework when it is output to the user.

It's also important to make sure that any .htaccess or web.config file
under assets is made writable by the webserver, as this module will
modify it as necessary to apply custom permissions.
