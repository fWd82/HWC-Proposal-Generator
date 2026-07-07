<?php

declare(strict_types=1);

$path = dirname(__DIR__) . '/data/services.json';
$services = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

$catalog = [
    'Elastic Cloud Server' => ['ECS', 'Secure, scalable virtual compute resources for cloud applications.', 'https://www.huaweicloud.com/intl/en-us/product/ecs.html'],
    'Elastic Volume Service' => ['EVS', 'Persistent block storage for cloud servers and enterprise workloads.', 'https://www.huaweicloud.com/intl/en-us/product/evs.html'],
    'Object Storage Service' => ['OBS', 'Scalable and durable object storage for files and unstructured data.', 'https://www.huaweicloud.com/intl/en-us/product/obs.html'],
    'Cloud Backup and Recovery' => ['CBR', 'Centralized backup and recovery for cloud and on-premises resources.', 'https://www.huaweicloud.com/intl/en-us/product/cbr.html'],
    'Scalable File Service Turbo' => ['SFS Turbo', 'High-performance managed shared file storage for cloud workloads.', 'https://www.huaweicloud.com/intl/en-us/product/sfsturbo.html'],
    'Virtual Private Cloud' => ['VPC', 'Isolated private networks for securely organizing cloud resources.', 'https://www.huaweicloud.com/intl/en-us/product/vpc.html'],
    'Elastic IP' => ['EIP', 'Static public IP connectivity for supported cloud resources.', 'https://www.huaweicloud.com/intl/en-us/product/eip.html'],
    'Elastic Load Balance' => ['ELB', 'Traffic distribution across backend servers for scalable, highly available applications.', 'https://www.huaweicloud.com/intl/en-us/product/elb.html'],
    'NAT Gateway' => ['NAT', 'Managed network address translation for cloud and on-premises servers.', 'https://www.huaweicloud.com/intl/en-us/product/nat.html'],
    'Virtual Private Network' => ['VPN', 'Secure IPsec connectivity between local data centers and Huawei Cloud.', 'https://www.huaweicloud.com/intl/en-us/product/vpn.html'],
    'Relational Database Service' => ['RDS', 'Managed relational database capabilities with simplified administration.', 'https://www.huaweicloud.com/intl/en-us/product/mysql.html'],
    'RDS for MySQL' => ['RDS for MySQL', 'A fully managed, MySQL-compatible relational database service.', 'https://www.huaweicloud.com/intl/en-us/product/mysql.html'],
    'GaussDB' => ['GaussDB', 'An enterprise-grade distributed relational database for critical workloads.', 'https://www.huaweicloud.com/intl/en-us/product/gaussdb.html'],
    'Document Database Service' => ['DDS', 'A scalable managed document database service.', 'https://www.huaweicloud.com/intl/en-us/product/dds.html'],
    'Distributed Cache Service' => ['DCS', 'A managed in-memory caching service compatible with Redis.', 'https://www.huaweicloud.com/intl/en-us/product/dcs.html'],
    'Data Replication Service' => ['DRS', 'Online database migration and synchronization with minimal downtime.', 'https://www.huaweicloud.com/intl/en-us/product/drs.html'],
    'Cloud Container Engine' => ['CCE', 'An enterprise managed Kubernetes service for containerized applications.', 'https://www.huaweicloud.com/intl/en-us/product/cce.html'],
    'Web Application Firewall' => ['WAF', 'Managed protection for websites and web applications against common attacks.', 'https://www.huaweicloud.com/intl/en-us/product/waf.html'],
    'Host Security Service' => ['HSS', 'Cloud host protection for asset management, intrusion prevention, and vulnerability management.', 'https://www.huaweicloud.com/intl/en-us/product/hss.html'],
    'Identity and Access Management' => ['IAM', 'Identity, permission, and access control for cloud services and resources.', 'https://www.huaweicloud.com/intl/en-us/product/iam.html'],
    'Cloud Eye' => ['CES', 'Monitoring, metrics, and alarms for cloud resources and applications.', 'https://www.huaweicloud.com/intl/en-us/product/ces.html'],
    'Content Delivery Network' => ['CDN', 'Distributed content delivery for fast and scalable user access.', 'https://www.huaweicloud.com/intl/en-us/product/cdn.html'],
    'Cloud Firewall' => ['CFW', 'Cloud-native traffic control and intrusion prevention across network boundaries.', 'https://www.huaweicloud.com/intl/en-us/product/cfw.html'],
];

foreach ($catalog as $name => [$code, $description, $link]) {
    $existing = $services[$name] ?? [];
    $services[$name] = array_merge([
        'code' => $code,
        'name' => $name,
        'short_description' => $description,
        'definition' => $description,
        'details' => [
            "The service is managed through Huawei Cloud and integrates with related cloud resources.",
            "Configuration and capacity can be selected according to solution and workload requirements.",
        ],
        'key_benefits' => [
            'Managed Huawei Cloud service',
            'Integration with the wider Huawei Cloud platform',
            'Flexible configuration aligned to workload requirements',
        ],
        'typical_use_cases' => [
            'Enterprise cloud environments',
            'Production application workloads',
            'Cloud modernization and migration initiatives',
        ],
        'proposal_positioning' => "{$name} is included as part of the proposed Huawei Cloud solution.",
        'diagram' => '',
        'link' => $link,
    ], $existing, ['link' => $link]);
}

file_put_contents(
    $path,
    json_encode($services, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL
);
