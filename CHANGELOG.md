# Changelog

:warning: There could be breaking changes anywhere during major version zero(v0.x.x).

## v0.2.0 (Unreleased)

### Breaking changes

- Special:ShareAchievementBadge is renamed to Special:ShareAchievement
- The prefix of the achievement system messages changed.
  `achievement-name-foo` &rarr; `achievementbadges-achievement-name-foo`
  `achievement-hint-foo` &rarr; `achievementbadges-achievement-hint-foo`
  `achievement-description-foo` &rarr; `achievementbadges-achievement-description-foo`

### Enhancements

- Special:ShareAchievement
  - Use English title always to avoid very long url which is built by `urlencode()`
  - Change the message of tweet when the user who sends the tweet is not the obtainer.
  - Add `<meta name="title">`
- `Special:Achievements/<OTHER_USERNAME>` will show the list of the other user's achieved achievements.
- Add a contributions tool link that links to Special:Achievements.

### Others

- Do not require Extension:Echo. it is just an option now.

## v0.1.1

- Fix bad url on sharing to Facebook

## v0.1.0

### Breaking changes

- Relicense under AGPL-3.0
- $wgAchievementBadgesAchievementFallbackIcon - This setting is now relative to $wgScriptPath.

### New configuration

- $wgAchievementBadgesAchievementFallbackOpenGraphImage - This is the path to the fallback image that displays as an Open Graph image Special:ShareAchievementBadge. This is relative to $wgScriptPath and its MIME type should be one of image/jpeg, image/gif or image/png.
- $wgAchievementBadgesFacebookAppId - If defined, a link to share to Facebook appears in Special:ShareAchievementBadge

### Enhancements

- Special:ShareAchievementBadge
  - Show additional message to disabled users and anon users
  - Add share buttons.
  - Provide `<meta name="description">` and `<meta property="og:image">`.
  - Add link for Special:Achievements.
  - Encode the subpage with base64
- Add new achievements: thanks and be-thanked
- Add links to notifications.
- Bundle achievement notifications.

### Developer changes

- Add Achievement::getQueryInfo().

#### Achievement registering changes

- New properties:
  - `og-image` - An image URL which should represent the achievement within the graph. This is relative to $wgScriptPath. If it is not specified and `icon` property is present, `icon` is used for this.

### Other changes

- Run Phan.
- Run prettier on YAML.

## v0.0.2

### New configuration

- $wgAchievementBadgesAchievementFallbackIcon - This is the path to the fallback icons that displays in Special:Achievements.
- $wgAchievementBadgesReplaceEchoThankYouEdit - When this is set to true, the edit milestone notifications offered by Echo will be not sent.
- $wgAchievementBadgesDisabledAchievements - This is a list contains keys of achievements that should be unregistered. Wiki administrators can use this to disable a built-in achievement.

### Enhancements

- Add new achievements:
  - contribs-{sun,mon,tues,wednes,thurs,fri,satur}day
  - create-page
  - edit-page
  - edit-size
  - enable-achievement-badges
  - long-user-page
  - sign-up
  - visual-edit
- Changes in Special:Achievements:
  - Add icon, body text and timestamp to each achievement.
  - Add an animation.
- Log achievements to Special:Log and show on Special:Achievements.
- Add a icon to BetaFeatures preference and Echo notifications.
- Link to the achievement fragment from notification.
- Add experimental Special:ShareAchievementBadge.

### Bug Fixes

- Do not allow system users to achieve achievements.
- Suppress Echo's welcome notification when it is replaced with the sign-up achievement.
- Fix duplicated items on Special:Achievements
- Do not allow user who disables AB to earn achievement

### Developer changes

- Hook calling uses new HookContainer.
- Add SpecialAchievementsBeforeGetEarned Hook.

#### Achievement registering changes

- New properties:
  - `type` - There is now two type: `'instant'` and `'stats'`. Read [extension.json](extension.json) for details.
  - `priority` - This is used to ordering on Special:Achievements. (default: 1000)

### Other changes

- Introduce Quibble tests.
- Store loggers in classes as a member variable.
- Use LocalUserCreatedHook instead of AddNewAccountHook
- Use a configuration variable and the ExtensionFunction to register achievements.

## v0.0.1

- Run prettier on Markdown.
- Provide basic process for registering and firing a achievement.
- Make the extension could be provided as a beta feature.
- Add a bare special page Special:Achievements.
- Add a Github actions workflow to lint PHP, JSON and LESS.
