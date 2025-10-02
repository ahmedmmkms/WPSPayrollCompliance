# Environment Topology

```mermaid
graph TD
    subgraph Client
        Browser
    end

    Browser -->|HTTPS| CloudflareCDN[Cloudflare DNS / SSL]
    CloudflareCDN --> AlwaysdataApp[Alwaysdata Web Application (Laravel)]
    AlwaysdataApp -->|MySQL TLS| PlanetScale[(PlanetScale MySQL)]
    AlwaysdataApp -->|Redis TLS| Upstash[(Upstash Redis)]
    GitHubActions[GitHub Actions] -->|Git Push| AlwaysdataApp
```

**Legend**
- **Alwaysdata Web Application** hosts the Laravel production app on the free tier.
- **PlanetScale** stores tenant data with branch-based schema management.
- **Upstash Redis** powers queues and caching.
- **Observability** is manual via Alwaysdata logs until a monitoring stack is selected.
- **On-demand exports** are streamed directly from the Laravel app; rerun jobs to regenerate historical files.
- **GitHub Actions** handles CI, artifact publication, and deploy hook execution.

Update this diagram when endpoints or integrations change.
