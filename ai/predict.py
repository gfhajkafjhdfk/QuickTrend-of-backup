#!/usr/bin/env python3
import json
import sys

def load_input():
    raw = sys.stdin.read()
    return json.loads(raw)


def score_candidate(user, candidate):
    score = 0
    if user.get('progress') and candidate.get('genre') == user.get('genre'):
        score += 40
    if candidate.get('name'):
        score += len(candidate['name']) % 10
    if candidate.get('profile'):
        score += min(20, len(candidate['profile']))
    return score


def create_matches(data):
    user = data['user']
    candidates = data['candidates']
    matches = []
    for candidate in candidates:
        score = score_candidate(user, candidate)
        matches.append({
            'id': candidate.get('id'),
            'name': candidate.get('name', '匿名'),
            'genre': candidate.get('genre', 'unknown'),
            'score': score,
            'profile': candidate.get('profile', ''),
        })
    matches = sorted(matches, key=lambda item: item['score'], reverse=True)
    return matches[:5]


def main():
    data = load_input()
    matches = create_matches(data)
    print(json.dumps({'matches': matches}, ensure_ascii=False))


if __name__ == '__main__':
    main()
