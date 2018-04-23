const {writeFile} = require('fs');
const RefParser = require('json-schema-ref-parser');
const toJsonSchema = require('openapi-schema-to-json-schema');

if (process.argv.length != 4) {
    console.log('Usage: node openapi2jsonschema.js [sourceFile] [targetFile]');
    process.exitCode = 1;
} else {
    const sourceFile = process.argv[2];
    const targetFile = process.argv[3];

    // Parse the OpenAPI description and replace the schemas with JSON schemas.
    RefParser.dereference(sourceFile).then(schema => {
        for (let path of Object.values(schema.paths)) {
            for (let method of ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace']) {
                if (path[method]) {
                    let operation = path[method];
                    for (let response of Object.values(operation.responses)) {
                        if (response.content) {
                            for (let content of Object.values(response.content)) {
                                if (content.schema) {
                                    content.schema = toJsonSchema(content.schema);
                                }
                            }
                        }
                    }
                }
            }
        }

        return schema;
    }).then(schema => writeFile(targetFile, JSON.stringify(schema)));
}
