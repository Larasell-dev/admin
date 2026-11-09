import {
  Container,
  createPlayer,
  FullscreenButton,
  MuteButton,
  PlayButton,
  Time,
  TimeSlider,
} from "@videojs/react";
import { Video, videoFeatures } from "@videojs/react/video";
import { HlsVideo } from "@videojs/react/media/hls-video";
import { Icon, IconButton } from "@larasell-dev/ui";
import {
  FullscreenExitLine,
  FullscreenLine,
  PauseLine,
  PlayLine,
  VolumeMuteLine,
  VolumeUpLine,
} from "@larasell-dev/ui/icons";
import { fbs } from "fbtee";
import type { ComponentProps } from "react";

const { Player } = createPlayer({ features: videoFeatures });

type Props = { src: string; width: number | null; height: number | null };

/**
 * A video with the panel's own controls below it. The frame takes the video's shape from its
 * poster's size before anything loads, so it never starts small and jumps; 16:9 until there's a
 * poster. It shows the video's own first frame, not the poster image.
 */
export default function VideoPlayer({ src, width, height }: Props) {
  const ratio = width && height ? width / height : 16 / 9;

  return (
    <Player>
      <Container className="flex size-full flex-col">
        {/* Sized against the space left for it, so the frame fits whichever side is shorter. */}
        <div
          className="flex min-h-0 flex-1 items-center justify-center p-6"
          style={{ containerType: "size" }}
        >
          <div
            data-testid="video-frame"
            className="relative overflow-hidden"
            style={{ aspectRatio: ratio, width: `min(100cqw, calc(100cqh * ${ratio}))` }}
          >
            {/* Mux streams as HLS, which only Safari plays on its own. */}
            <VideoElement
              src={src}
              playsInline // "auto", so every browser, Safari too, draws the first frame before playing.
              preload="auto"
              className="size-full object-contain"
            />
          </div>
        </div>
        <div className="flex items-center gap-3 border-t border-base-subtle bg-base-canvas px-4 py-2">
          <PlayButton
            render={(props, state) => (
              <IconButton
                {...props}
                variant="tertiary"
                aria-label={
                  state.paused
                    ? fbs("Play", "Media page: accessible name of the button that starts the video")
                    : fbs(
                        "Pause",
                        "Media page: accessible name of the button that pauses the video",
                      )
                }
              >
                <Icon icon={state.paused ? PlayLine : PauseLine} />
              </IconButton>
            )}
          />
          <Time.Value type="current" className="type-body-sm text-base-secondary tabular-nums" />
          <TimeSlider.Root
            label={fbs("Seek", "Media page: accessible name of the video's timeline slider")}
            className="group relative flex h-6 flex-1 cursor-pointer items-center"
          >
            <TimeSlider.Track className="relative h-1 w-full overflow-hidden rounded-full bg-base-muted">
              <TimeSlider.Buffer className="absolute inset-y-0 left-0 w-(--media-slider-buffer) bg-base-overlay" />
              <TimeSlider.Fill className="absolute inset-y-0 left-0 w-(--media-slider-fill) bg-action-neutral-primary-default" />
            </TimeSlider.Track>
            <TimeSlider.Thumb className="absolute left-(--media-slider-fill) size-3 -translate-x-1/2 rounded-full bg-action-neutral-primary-default" />
          </TimeSlider.Root>
          <Time.Value type="duration" className="type-body-sm text-base-secondary tabular-nums" />
          <div className="flex">
            <MuteButton
              render={(props, state) => (
                <IconButton
                  {...props}
                  variant="tertiary"
                  aria-label={
                    state.muted
                      ? fbs(
                          "Unmute",
                          "Media page: accessible name of the button that turns the video's sound on",
                        )
                      : fbs(
                          "Mute",
                          "Media page: accessible name of the button that turns the video's sound off",
                        )
                  }
                >
                  <Icon icon={state.muted ? VolumeMuteLine : VolumeUpLine} />
                </IconButton>
              )}
            />
            <FullscreenButton
              render={(props, state) => (
                <IconButton
                  {...props}
                  variant="tertiary"
                  aria-label={
                    state.fullscreen
                      ? fbs(
                          "Exit fullscreen",
                          "Media page: accessible name of the button that leaves fullscreen video",
                        )
                      : fbs(
                          "Enter fullscreen",
                          "Media page: accessible name of the button that shows the video fullscreen",
                        )
                  }
                >
                  <Icon icon={state.fullscreen ? FullscreenExitLine : FullscreenLine} />
                </IconButton>
              )}
            />
          </div>
        </div>
      </Container>
    </Player>
  );
}

function VideoElement(props: ComponentProps<typeof Video>) {
  return typeof props.src === "string" &&
    new URL(props.src, location.href).pathname.endsWith(".m3u8") ? (
    <HlsVideo {...(props as ComponentProps<typeof HlsVideo>)} />
  ) : (
    <Video {...props} />
  );
}
