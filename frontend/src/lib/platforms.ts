// What each platform's key works on and how to activate it.
// Shown on the product page and next to delivered keys.

export interface PlatformInfo {
  worksOn: string
  isPc: boolean // PC platforms show system requirements
  activation: string[]
}

const PLATFORMS: Record<string, PlatformInfo> = {
  Steam: {
    worksOn: 'Windows',
    isPc: true,
    activation: [
      'Download and install the Steam client, then log in to your account.',
      'Open the "Games" menu and choose "Activate a Product on Steam…".',
      'Enter your key and follow the instructions. The game appears in your library.',
    ],
  },
  'EA App': {
    worksOn: 'Windows',
    isPc: true,
    activation: [
      'Download and install the EA app, then sign in.',
      'Open the menu and choose "Redeem code".',
      'Enter your key. The game is added to your library.',
    ],
  },
  Rockstar: {
    worksOn: 'Windows',
    isPc: true,
    activation: [
      'Download and install the Rockstar Games Launcher, then sign in.',
      'Go to Settings, then "Account information" and "Redeem code".',
      'Enter your key and confirm.',
    ],
  },
  Xbox: {
    worksOn: 'Xbox One, Xbox Series X|S',
    isPc: false,
    activation: [
      'On your console open the Microsoft Store, or go to redeem.microsoft.com in a browser and sign in.',
      'Choose "Redeem a code".',
      'Enter the 25-character key and confirm.',
    ],
  },
  PlayStation: {
    worksOn: 'PlayStation 4, PlayStation 5',
    isPc: false,
    activation: [
      'Sign in to PlayStation Store on your console or in a browser.',
      'Open your profile menu and choose "Redeem codes".',
      'Enter the key and confirm.',
    ],
  },
}

const FALLBACK: PlatformInfo = {
  worksOn: 'See platform',
  isPc: false,
  activation: ["Redeem the key in the platform's store or launcher."],
}

export function platformInfo(platform: string): PlatformInfo {
  return PLATFORMS[platform] ?? FALLBACK
}

export function regionLabel(region: string): string {
  return region === 'GLOBAL' ? 'Can be activated worldwide' : `Can be activated in ${region.toLowerCase().replace(/^./, (c) => c.toUpperCase())}`
}
