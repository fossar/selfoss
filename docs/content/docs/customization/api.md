+++
title = "API"
weight = 50
+++

selfoss offers a RESTful JSON API for accessing or changing all selfoss data. You can use this API in your custom selfoss client app or any other program or plug-in.

The API endpoinds are specified using [OpenAPI](https://swagger.io/specification/) description format, which you will find in the [selfoss repository](https://github.com/fossar/selfoss/blob/master/docs/api-description.json). Or you can view it in a more human-readable format on [SwaggerHub](https://app.swaggerhub.com/apis-docs/jtojnar/selfoss). There are also many tools that allow generating boilerplate code from the API description.

Since selfoss 2.19, the API follows [semantic versioning](https://semver.org/) distinct from selfoss itself to allow tracking API changes in development snapshots. Below, you can see which selfoss release offers which API version:

| selfoss | API |
|---|---|
| [2.19] | [6.0.0] |

[2.19]: https://github.com/fossar/selfoss/releases/tag/2.19
[6.0.0]: https://app.swaggerhub.com/apis-docs/jtojnar/selfoss/6.0.0
