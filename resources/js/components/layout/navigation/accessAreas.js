import { Globe, KeyRound, ShieldCheck, Users } from 'lucide-vue-next'

export const accessAreas = [
  {
    name: `Users`,
    area: `users`,
    route: `admin.users.index`,
    icon: Users,
    description: `Manage staff accounts and their assigned roles.`,
  },
  {
    name: `Roles`,
    area: `role`,
    route: `admin.role.index`,
    icon: ShieldCheck,
    description: `Choose which permissions each role grants.`,
  },
  {
    name: `Permissions`,
    area: `permissions`,
    route: `admin.permissions.index`,
    icon: KeyRound,
    description: `Manage individual application permissions.`,
  },
  {
    name: `Allowed Domains`,
    area: `allowedDomains`,
    route: `admin.allowed-domains.index`,
    icon: Globe,
    description: `Choose the email domains allowed to sign in.`,
  },
]
