// raspap-network-monitor.c

/*
RaspAP Network Activity Monitor
Author: @billz <billzimmerman@gmail.com>
Author URI: https://github.com/billz/
License: GNU General Public License v3.0
License URI: https://github.com/raspap/raspap-webgui/blob/master/LICENSE

Usage: raspap-network-monitor [interface]
*/

#define _GNU_SOURCE
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <unistd.h>
#include <fcntl.h>
#include <poll.h>
#include <sys/timerfd.h>
#include <errno.h>
#include <stdint.h>

#define TMPFILE "/dev/shm/net_activity"
#define POLL_INTERVAL_MS 100 // 100 milliseconds

unsigned long read_interface_bytes(const char *iface) {
    FILE *fp = fopen("/proc/net/dev", "r");
    if (!fp) return 0;

    char line[512];
    unsigned long rx = 0, tx = 0;

    while (fgets(line, sizeof(line), fp)) {
        if (strstr(line, iface)) {
            char *ptr = strchr(line, ':');
            if (ptr) {
                sscanf(ptr + 1, "%lu %*u %*u %*u %*u %*u %*u %*u %lu", &rx, &tx);
            }
            break;
        }
    }

    fclose(fp);
    return rx + tx;
}

int main(int argc, char *argv[]) {
    if (argc < 2) {
        fprintf(stderr, "Usage: %s <interface>\n", argv[0]);
        return EXIT_FAILURE;
    }

    const char *iface = argv[1];
    unsigned long prev_total = read_interface_bytes(iface);

    // create a timerfd
    int tfd = timerfd_create(CLOCK_MONOTONIC, 0);
    if (tfd == -1) {
        perror("timerfd_create");
        return EXIT_FAILURE;
    }

    struct itimerspec timer;
    timer.it_interval.tv_sec = 0;
    timer.it_interval.tv_nsec = POLL_INTERVAL_MS * 1000000; // interval
    timer.it_value.tv_sec = 0;
    timer.it_value.tv_nsec = POLL_INTERVAL_MS * 1000000;    // initial expiration

    if (timerfd_settime(tfd, 0, &timer, NULL) == -1) {
        perror("timerfd_settime");
        close(tfd);
        return EXIT_FAILURE;
    }

    struct pollfd fds;
    fds.fd = tfd;
    fds.events = POLLIN;

    for (;;) {
        int ret = poll(&fds, 1, -1);
        if (ret == -1) {
            perror("poll");
            break;
        }

        if (fds.revents & POLLIN) {
            uint64_t expirations;
            read(tfd, &expirations, sizeof(expirations)); // clear timer

            unsigned long curr_total = read_interface_bytes(iface);
            unsigned long diff = (curr_total >= prev_total) ? (curr_total - prev_total) : 0;
            prev_total = curr_total;

            FILE *out = fopen(TMPFILE, "w");
            if (out) {
                fprintf(out, "%lu\n", diff);
                fclose(out);
            }
        }
    }

    close(tfd);
    return EXIT_SUCCESS;
}

